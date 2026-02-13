<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate 
                            {--force : Força a execução sem confirmação}
                            {--seed : Executa seeders após as migrations}
                            {--fresh : Dropa todas as tabelas antes de migrar}';

    protected $description = 'Executa migrations: banco principal (default) e depois em cada banco listado em database_models (só roda migrations, não cria nem apaga banco)';

    protected string $defaultConnection;

    public function handle(): int
    {
        $this->defaultConnection = config('database.default');

        $this->info('🚀 Iniciando execução de migrations...');
        $this->newLine();

        $force = $this->option('force');
        $seed = $this->option('seed');
        $fresh = $this->option('fresh');

        if (! $force && ! $this->confirm('Deseja continuar? Isso executará migrations em múltiplos bancos.', false)) {
            $this->warn('Operação cancelada.');

            return self::FAILURE;
        }

        // PASSO 1: Migrar banco principal (conexão default)
        $this->info('📦 PASSO 1: Migrando banco principal...');
        if (! $this->migratePrincipalDatabase($fresh, $seed)) {
            return self::FAILURE;
        }

        // PASSO 2: Para cada database_models, ler registros com database preenchido e rodar migrations (paths)
        $this->newLine();
        $this->info('📦 PASSO 2: Migrando bancos (tenants/clients/stores) conforme database_models...');
        $this->migrateDatabasesFromConfig($fresh, $seed);

        $this->newLine();
        $this->info('✅ Todas as migrations foram executadas com sucesso!');

        return self::SUCCESS;
    }

    /**
     * Migra o banco principal (conexão default) com migrations da pasta principal
     */
    protected function migratePrincipalDatabase(bool $fresh, bool $seed): bool
    {
        $paths = config('raptor.migrations.default', 'database/migrations');
        $paths = is_array($paths) ? $paths : [$paths];
        $this->info("   → Banco: {$this->defaultConnection}");
        $this->info('   → Pasta(s): '.implode(', ', $paths));

        try {
            $baseOptions = [
                '--database' => $this->defaultConnection,
                '--realpath' => false,
            ];

            $first = true;
            foreach ($paths as $path) {
                if ($fresh && $first) {
                    $this->call('migrate:fresh', array_merge($baseOptions, ['--path' => $path]));
                    $first = false;
                } else {
                    $this->call('migrate', array_merge($baseOptions, ['--path' => $path]));
                }
            }

            if ($seed) {
                $this->call('db:seed', ['--database' => $this->defaultConnection]);
            }

            $this->info('   ✅ Banco principal migrado com sucesso!');

            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Erro ao migrar banco principal: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Para cada database_models: lê o model, registros com database preenchido, e roda as migrations em paths.
     */
    protected function migrateDatabasesFromConfig(bool $fresh, bool $seed): void
    {
        $databases = $this->collectDatabasesFromModels();

        if (empty($databases)) {
            $this->warn('   Nenhum banco encontrado nos database_models (campo database preenchido).');

            return;
        }

        $this->info(sprintf('   Encontrados %d banco(s) para migrar:', count($databases)));
        $this->newLine();

        $this->table(
            ['Tipo', 'Nome', 'Banco de Dados'],
            collect($databases)->map(fn ($db) => [$db['type'], $db['name'], $db['database']])->toArray()
        );

        $this->newLine();

        $successCount = 0;
        $errorCount = 0;

        foreach ($databases as $db) {
            $result = $this->runMigrationsOnDatabase($db, $fresh, $seed);
            $result ? $successCount++ : $errorCount++;
        }

        $this->newLine();
        $this->table(
            ['Status', 'Quantidade'],
            [
                ['✅ Sucesso', $successCount],
                ['❌ Erro', $errorCount],
            ]
        );
    }

    /**
     * Percorre database_models: para cada entry, busca registros com database preenchido e monta lista com paths.
     *
     * @return array<int, array{type: string, name: string, database: string, paths: array<int, string>}>
     */
    protected function collectDatabasesFromModels(): array
    {
        $entries = config('raptor.migrations.database_models', []);
        $list = [];

        foreach ($entries as $entry) {
            $paths = $entry['paths'] ?? [];
            if (empty($paths) || ! is_array($paths)) {
                continue;
            }

            $modelClass = $this->resolveModelClass($entry['model'] ?? null);
            if (! $modelClass || ! class_exists($modelClass)) {
                continue;
            }

            $type = $entry['type'] ?? class_basename($modelClass);
            $nameKey = $entry['name_key'] ?? 'name';

            $records = $modelClass::query()
                ->whereNotNull('database')
                ->where('database', '!=', '')
                ->get();

            if ($records->isEmpty()) {
                continue;
            }

            foreach ($records as $record) {
                $list[] = [
                    'type' => $type,
                    'name' => $record->{$nameKey} ?? (string) $record->getKey(),
                    'database' => $record->database,
                    'paths' => $paths,
                ];
            }
        }

        return $list;
    }

    /**
     * Resolve a classe do model: se for chave de config (ex: raptor.landlord.models.tenant), usa config(); senão assume que é FQCN.
     */
    protected function resolveModelClass(?string $model): ?string
    {
        if ($model === null || $model === '') {
            return null;
        }

        if (str_starts_with($model, 'raptor.') || str_starts_with($model, 'config.')) {
            $resolved = config(str_starts_with($model, 'config.') ? $model : $model);

            return is_string($resolved) ? $resolved : null;
        }

        return $model;
    }

    /**
     * Roda as migrations nos paths configurados para um banco. Não cria nem apaga banco.
     *
     * @param  array{type: string, name: string, database: string, paths: array<int, string>}  $db
     */
    protected function runMigrationsOnDatabase(array $db, bool $fresh, bool $seed): bool
    {
        $database = $db['database'];
        $type = $db['type'];
        $name = $db['name'];
        $paths = $db['paths'];

        $this->info("   🔄 Migrando [{$type}] {$name} → {$database}");

        try {
            $this->setupTenantConnection($database);

            if (! $this->databaseExists($database)) {
                $this->warn("      ⚠️  Banco '{$database}' não existe. Não criamos banco aqui — pule.");

                return false;
            }

            $mainOptions = ['--database' => 'tenant', '--realpath' => false];
            $first = true;

            foreach ($paths as $path) {
                $fullPath = base_path($path);
                if (! is_dir($fullPath)) {
                    $fullPath = $path;
                }
                if (! is_dir($fullPath)) {
                    $this->warn("      Pasta não encontrada: {$path}");
                    continue;
                }
                $this->info("      📁 Migrando {$path}...");
                $opts = array_merge($mainOptions, ['--path' => $path]);
                if ($fresh && $first) {
                    Artisan::call('migrate:fresh', $opts);
                    $first = false;
                } else {
                    Artisan::call('migrate', $opts);
                }
            }

            if ($seed) {
                $this->info('      🌱 Executando seeders...');
                Artisan::call('db:seed', ['--database' => 'tenant']);
            }

            $this->info('      ✅ Migrado com sucesso!');

            return true;

        } catch (\Exception $e) {
            $this->error("      ❌ Erro: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Configura a conexão "tenant" dinamicamente
     */
    protected function setupTenantConnection(string $database): void
    {
        $defaultConfig = config("database.connections.{$this->defaultConnection}");

        Config::set('database.connections.tenant', array_merge($defaultConfig, [
            'database' => $database,
        ]));

        // Limpa cache de conexão
        DB::purge('tenant');
    }

    /**
     * Verifica se o banco de dados existe
     */
    protected function databaseExists(string $database): bool
    {
        try {
            $driver = config("database.connections.{$this->defaultConnection}.driver");

            if ($driver === 'pgsql') {
                $result = DB::connection($this->defaultConnection)
                    ->select('SELECT 1 FROM pg_database WHERE datname = ?', [$database]);
            } else {
                $result = DB::connection($this->defaultConnection)
                    ->select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$database]);
            }

            return count($result) > 0;

        } catch (\Exception $e) {
            return false;
        }
    }

}
