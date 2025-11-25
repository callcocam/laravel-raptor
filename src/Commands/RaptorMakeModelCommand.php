<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class RaptorMakeModelCommand extends GeneratorCommand
{
    protected $name = 'raptor:make-model';

    protected $description = 'Create a new Raptor model class';

    protected $type = 'Model';

    protected function getStub(): string
    {
        return __DIR__ . '/../../stubs/model.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Models';
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $tableName = $this->option('table') ?? Str::plural(Str::snake(class_basename($name)));

        // Determina se deve usar factory
        $useFactory = $this->hasFactory($name);
        
        if ($useFactory) {
            $stub = str_replace(
                '{{ factoryImport }}',
                "use Illuminate\Database\Eloquent\Factories\HasFactory;",
                $stub
            );
            $stub = str_replace(
                '{{ factory }}',
                "use HasFactory;\n",
                $stub
            );
        } else {
            $stub = str_replace('{{ factoryImport }}', '', $stub);
            $stub = str_replace('{{ factory }}', '', $stub);
        }

        // Adiciona informações da tabela se existir
        if (Schema::hasTable($tableName)) {
            $fillable = $this->getFillableColumns($tableName);
            $casts = $this->getCasts($tableName);

            $fillableString = $this->generateFillableArray($fillable);
            $castsString = $this->generateCastsMethod($casts);

            // Adiciona fillable e casts antes do fechamento da classe
            $stub = str_replace(
                'use SoftDeletes;',
                "use SoftDeletes;\n\n    protected \$fillable = {$fillableString};\n{$castsString}",
                $stub
            );
        }

        return $stub;
    }

    protected function hasFactory(string $name): bool
    {
        $factoryName = class_basename($name) . 'Factory';
        $factoryPath = database_path("factories/{$factoryName}.php");
        
        return file_exists($factoryPath);
    }

    protected function getFillableColumns(string $table): array
    {
        $columns = Schema::getColumnListing($table);
        
        // Remove colunas padrão que não devem ser fillable
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_at'];
        
        return array_diff($columns, $exclude);
    }

    protected function getCasts(string $table): array
    {
        $casts = [];
        $columns = Schema::getColumns($table);

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $column['type_name'];

            // Mapeia tipos do banco para casts do Laravel
            $castType = match ($type) {
                'boolean', 'bool' => 'boolean',
                'integer', 'int', 'bigint' => 'integer',
                'float', 'double', 'decimal' => 'decimal:2',
                'date' => 'date',
                'datetime', 'timestamp' => 'datetime',
                'json', 'jsonb' => 'array',
                default => null
            };

            if ($castType && !in_array($name, ['created_at', 'updated_at', 'deleted_at'])) {
                $casts[$name] = $castType;
            }
        }

        return $casts;
    }

    protected function generateFillableArray(array $fillable): string
    {
        if (empty($fillable)) {
            return '[]';
        }

        $items = array_map(fn($item) => "'{$item}'", $fillable);
        
        return "[\n        " . implode(",\n        ", $items) . ",\n    ]";
    }

    protected function generateCastsMethod(array $casts): string
    {
        if (empty($casts)) {
            return '';
        }

        $lines = [];
        foreach ($casts as $field => $type) {
            $lines[] = "            '{$field}' => '{$type}',";
        }

        return "\n\n    protected function casts(): array\n    {\n        return [\n" . 
               implode("\n", $lines) . 
               "\n        ];\n    }";
    }

    protected function getOptions(): array
    {
        return [
            ['table', 't', InputOption::VALUE_OPTIONAL, 'The database table name'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
        ];
    }
}
