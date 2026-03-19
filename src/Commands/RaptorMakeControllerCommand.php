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

class RaptorMakeControllerCommand extends GeneratorCommand
{
    protected $name = 'raptor:make-controller';

    protected $description = 'Create a new Raptor controller class';

    protected $type = 'Controller';

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/controller.plain.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $context = $this->option('context') ?: 'Tenant';

        return $rootNamespace.'\Http\Controllers\\'.$context;
    }

    protected function qualifyClass($name): string
    {
        // Adiciona 'Controller' ao final se não estiver presente
        if (! str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        return parent::qualifyClass($name);
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $tableName = $this->option('table') ?? Str::plural(Str::snake(str_replace('Controller', '', class_basename($name))));
        $resourceName = Str::singular(Str::camel($tableName));
        $resourcePluralName = Str::plural($resourceName);
        $resourceTitle = Str::title(str_replace('_', ' ', $resourceName));
        $resourcePluralTitle = Str::plural($resourceTitle);
        $context = strtolower($this->option('context') ?: 'tenant');
        $routePrefix = $context === 'tenant' ? '' : $context.'.';

        // Substitui placeholders customizados
        $stub = str_replace('{{ resourceName }}', $resourceName, $stub);
        $stub = str_replace('{{ resourcePluralName }}', $resourcePluralName, $stub);
        $stub = str_replace('{{ resourceTitle }}', $resourceTitle, $stub);
        $stub = str_replace('{{ resourcePluralTitle }}', $resourcePluralTitle, $stub);
        $stub = str_replace('{{ tableName }}', $tableName, $stub);
        $stub = str_replace('{{ context }}', $context, $stub);
        $stub = str_replace('{{ routePrefix }}', $routePrefix, $stub);

        // Resolve model class and import
        $modelOption = $this->option('model');
        if ($modelOption) {
            $namespacedModel = $this->qualifyModel($modelOption);
            $modelClass = class_basename($namespacedModel);
            $modelImport = "use {$namespacedModel};";
        } else {
            $controllerBasename = str_replace('Controller', '', class_basename($name));
            $namespacedModel = $this->qualifyModel($controllerBasename);
            $modelClass = class_basename($namespacedModel);
            $modelImport = class_exists($namespacedModel) ? "use {$namespacedModel};" : '';
        }
        $stub = str_replace('{{ modelImport }}', $modelImport, $stub);
        $stub = str_replace('{{ modelClass }}', $modelClass, $stub);

        // Se a tabela existe, gera colunas baseadas no schema
        if (Schema::hasTable($tableName)) {
            $this->info("Generating controller based on table: {$tableName}");
            $stub = $this->generateColumnsFromTable($stub, $tableName);
        } else {
            $stub = str_replace('{{ formFields }}', $this->defaultFormFields(), $stub);
            $stub = str_replace('{{ tableColumns }}', $this->defaultTableColumns(), $stub);
        }

        return $stub;
    }

    protected function generateColumnsFromTable(string $stub, string $table): string
    {
        $columns = Schema::getColumns($table);

        // Filtra colunas que não devem aparecer nos forms
        $excludeFromForm = ['id', 'created_at', 'updated_at', 'deleted_at', 'tenant_id'];
        $formColumns = array_filter($columns, fn ($col) => ! in_array($col['name'], $excludeFromForm));

        // Gera campos do form
        $formFields = $this->generateFormFields($formColumns);

        // Gera colunas da tabela
        $tableColumns = $this->generateTableColumns($columns);

        $stub = str_replace('{{ formFields }}', $formFields, $stub);
        $stub = str_replace('{{ tableColumns }}', $tableColumns, $stub);

        return $stub;
    }

    protected function generateFormFields(array $columns): string
    {
        $fields = [];

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $column['type_name'];
            $nullable = $column['nullable'];
            $label = Str::title(str_replace('_', ' ', $name));

            $fieldType = $this->mapColumnToFormField($type);
            $required = $nullable ? '' : '->required()';
            $rules = $this->generateRules($column);

            $fields[] = <<<PHP
            \\Callcocam\\LaravelRaptor\\Support\\Form\\Columns\\Types\\{$fieldType}::make('{$name}')
                    ->label('{$label}')
                    {$required}
                    ->rules({$rules})
                    ->columnSpan('6'),
            PHP;
        }

        return implode("\n\n", $fields);
    }

    protected function generateTableColumns(array $columns): string
    {
        $tableColumns = [];

        // Limita a 5 colunas principais para a tabela
        $mainColumns = array_slice($columns, 0, 5);

        foreach ($mainColumns as $column) {
            if (in_array($column['name'], ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $name = $column['name'];
            $label = Str::title(str_replace('_', ' ', $name));

            $tableColumns[] = <<<PHP
            \\Callcocam\\LaravelRaptor\\Support\\Table\\Columns\\Types\\TextColumn::make('{$name}')
                    ->label('{$label}')
                    ->searchable()
                    ->sortable(),
            PHP;
        }

        return implode("\n\n", $tableColumns);
    }

    protected function mapColumnToFormField(string $type): string
    {
        return match ($type) {
            'text', 'longtext' => 'TextareaField',
            'boolean', 'bool' => 'SwitchField',
            'date' => 'DateField',
            'datetime', 'timestamp' => 'DateTimeField',
            'integer', 'bigint' => 'NumberField',
            'decimal', 'float', 'double' => 'NumberField',
            default => 'TextField'
        };
    }

    protected function generateRules(array $column): string
    {
        $rules = [];

        if (! $column['nullable']) {
            $rules[] = "'required'";
        }

        $type = $column['type_name'];

        if (in_array($type, ['varchar', 'string', 'text'])) {
            $rules[] = "'string'";
            if (isset($column['length'])) {
                $rules[] = "'max:{$column['length']}'";
            }
        }

        if (in_array($type, ['integer', 'bigint', 'int'])) {
            $rules[] = "'integer'";
        }

        if (in_array($type, ['decimal', 'float', 'double'])) {
            $rules[] = "'numeric'";
        }

        if ($type === 'email') {
            $rules[] = "'email'";
        }

        return '['.implode(', ', $rules).']';
    }

    protected function defaultFormFields(): string
    {
        return <<<'PHP'
            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('name')
                    ->label('Nome')
                    ->required()
                    ->rules(['required', 'string', 'max:255'])
                    ->columnSpan('6'),

            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextareaField::make('description')
                    ->label('Descrição')
                    ->rules([])
                    ->columnSpan('6'),
        PHP;
    }

    protected function defaultTableColumns(): string
    {
        return <<<'PHP'
            \Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

            \Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

            \Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn::make('description')
                    ->label('Descrição'),
        PHP;
    }

    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);
        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return $rootNamespace.'Models\\'.$model;
    }

    protected function getOptions(): array
    {
        return [
            ['table', 't', InputOption::VALUE_OPTIONAL, 'The database table name'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model class for this controller'],
            ['context', 'c', InputOption::VALUE_OPTIONAL, 'The context directory (Tenant, Landlord, Admin)', 'Tenant'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the controller already exists'],
        ];
    }
}
