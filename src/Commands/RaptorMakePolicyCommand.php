<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class RaptorMakePolicyCommand extends GeneratorCommand
{
    protected $name = 'raptor:make-policy';

    protected $description = 'Create a new Raptor policy class';

    protected $type = 'Policy';

    protected function getStub(): string
    {
        return __DIR__ . '/../../stubs/policy.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Policies';
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $model = $this->option('model');

        if (!$model) {
            $model = str_replace('Policy', '', class_basename($name));
        }

        $namespaceModel = $this->qualifyModel($model);
        $modelClass = class_basename($namespaceModel);
        $modelVariable = Str::camel($modelClass);
        $permission = Str::plural(Str::snake($modelClass));

        // Namespace do User model
        $userModel = $this->userProviderModel();

        $stub = str_replace('{{ namespacedModel }}', $namespaceModel, $stub);
        $stub = str_replace('{{ namespacedUserModel }}', $userModel, $stub);
        $stub = str_replace('{{ model }}', $modelClass, $stub);
        $stub = str_replace('{{ user }}', class_basename($userModel), $stub);
        $stub = str_replace('{{ modelVariable }}', $modelVariable, $stub);
        $stub = str_replace('{{ permission }}', $permission, $stub);

        return $stub;
    }

    protected function userProviderModel(): string
    {
        $config = $this->laravel['config'];

        $provider = $config->get('auth.guards.' . $config->get('auth.defaults.guard') . '.provider');

        return $config->get("auth.providers.{$provider}.model");
    }

    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return $rootNamespace . 'Models\\' . $model;
    }

    protected function getOptions(): array
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the policy already exists'],
        ];
    }
}
