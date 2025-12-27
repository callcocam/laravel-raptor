<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RaptorGenerateCommand extends Command
{
    protected $signature = 'raptor:generate
                            {name : The name of the resource}
                            {--table= : The database table name}
                            {--context=Tenant : The context directory (Tenant, Landlord, Admin)}
                            {--model : Generate model}
                            {--controller : Generate controller}
                            {--policy : Generate policy}
                            {--all : Generate all (model, controller, policy)}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate Raptor resources (Model, Controller, Policy) with optional database introspection';

    protected array $tableColumns = [];
    protected string $tableName = '';

    public function handle(): int
    {
        $name = $this->argument('name');
        $tableName = $this->option('table') ?? Str::plural(Str::snake($name));
        $this->tableName = $tableName;

        // Verifica se a tabela existe e obtém as colunas
        if (Schema::hasTable($tableName)) {
            $this->info("Table '{$tableName}' found. Analyzing columns...");
            $this->tableColumns = $this->getTableColumns($tableName);
        } else {
            $this->warn("Table '{$tableName}' not found. Generating basic structure...");
        }

        $generateAll = $this->option('all');

        if ($generateAll || $this->option('model')) {
            $this->call('raptor:make-model', [
                'name' => $name,
                '--table' => $tableName,
                '--force' => $this->option('force'),
            ]);
        }

        if ($generateAll || $this->option('controller')) {
            $this->call('raptor:make-controller', [
                'name' => $name,
                '--table' => $tableName,
                '--context' => $this->option('context') ?: 'Tenant',
                '--force' => $this->option('force'),
            ]);
        }

        if ($generateAll || $this->option('policy')) {
            $this->call('raptor:make-policy', [
                'name' => $name,
                '--model' => $name,
                '--force' => $this->option('force'),
            ]);
        }

        if (!$generateAll && !$this->option('model') && !$this->option('controller') && !$this->option('policy')) {
            $this->error('Please specify what to generate: --model, --controller, --policy, or --all');
            return self::FAILURE;
        }

        $this->info('✅ Generation completed successfully!');

        return self::SUCCESS;
    }

    protected function getTableColumns(string $table): array
    {
        $columns = [];
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL query to get column information
            $tableColumns = DB::select("
                SELECT 
                    column_name as name,
                    data_type as type,
                    is_nullable,
                    column_default as default_value
                FROM information_schema.columns
                WHERE table_name = ?
                ORDER BY ordinal_position
            ", [$table]);

            foreach ($tableColumns as $column) {
                $columns[] = [
                    'name' => $column->name,
                    'type' => $column->type,
                    'nullable' => $column->is_nullable === 'YES',
                    'default' => $column->default_value,
                    'key' => '',
                ];
            }
        } else {
            // MySQL/MariaDB query
            $tableColumns = DB::select("DESCRIBE {$table}");

            foreach ($tableColumns as $column) {
                $columns[] = [
                    'name' => $column->Field,
                    'type' => $column->Type,
                    'nullable' => $column->Null === 'YES',
                    'default' => $column->Default,
                    'key' => $column->Key,
                ];
            }
        }

        return $columns;
    }
}
