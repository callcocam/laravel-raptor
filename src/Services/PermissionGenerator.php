<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Concerns\GeneratesPermissionIds;
use Callcocam\LaravelRaptor\Enums\PermissionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class PermissionGenerator
{
    use GeneratesPermissionIds;

    /**
     * Conexão de banco para escrita das permissões (ex.: 'mysql' ou 'tenant').
     */
    protected string $connection;

    /**
     * Configuração de diretórios de controllers para scan.
     * Formato: ['namespace' => 'path']
     *
     * @var array<string, string>
     */
    protected array $controllerDirectories = [];

    public function __construct(array $defaultDirectories = [], ?string $connection = null)
    {
        $this->connection = $connection ?? config('database.default');
        $this->loadDefaultDirectories($defaultDirectories);
    }

    public static function generate(array $defaultDirectories = [], ?string $connection = null): self
    {
        return new self($defaultDirectories, $connection);
    }

    /**
     * Define a conexão usada para criar/atualizar permissões (ex.: 'tenant' para banco do inquilino).
     */
    public function forConnection(string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    protected function getPermissionsTable(): string
    {
        return config('raptor.shinobi.tables.permissions', 'permissions');
    }

    public function save($drop = false): void
    {
        $table = $this->getPermissionsTable();
        if ($drop) {
            DB::connection($this->connection)->table($table)->delete();
        }
        $this->registerRoutes();
    }

    public function getControllerDirectories(): array
    {
        return $this->controllerDirectories;
    }

    protected function registerRoutes(): void
    {
        foreach ($this->controllerDirectories as $namespace => $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            $controllers = $this->scanControllers($namespace, $path);

            foreach ($controllers as $controllerClass) {
                $this->registerControllerRoutes($controllerClass);
            }
        }
    }

    protected function scanControllers(string $namespace, string $path): array
    {
        $controllers = [];
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file, $namespace, $path);

            if ($className && $this->hasGetPagesMethod($className)) {
                $controllers[] = $className;
            }
        }

        return $controllers;
    }

    protected function getClassNameFromFile($file, string $namespace, string $basePath): ?string
    {
        $relativePath = str_replace($basePath.'/', '', $file->getPathname());
        $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
        $fullClassName = $namespace.'\\'.$className;

        if (class_exists($fullClassName)) {
            return $fullClassName;
        }

        return null;
    }

    protected function hasGetPagesMethod(string $className): bool
    {
        try {
            $reflection = new ReflectionClass($className);

            return $reflection->hasMethod('getPages') &&
                $reflection->getMethod('getPages')->isPublic();
        } catch (\Exception) {
            return false;
        }
    }

    protected function registerControllerRoutes(string $controllerClass): void
    {
        try {
            $controller = new $controllerClass;
            $pages = $controller->getPages();

            if (! is_array($pages)) {
                return;
            }
            $context = 'tenant';
            if (! str($controllerClass)->contains('Tenant')) {
                $context = 'landlord';
            }
            $data = [];
            foreach ($pages as $page) {
                // Lógica para registrar permissões com base nas páginas
                // Exemplo: Permission::firstOrCreate(['name' => $page['name'], 'route' => $page['route']]);
                $name = sprintf('%s - (%s)', $page->getLabel(), $page->getAction());
                $route = sprintf('%s.%s', $context, $page->getName());
                // Gera um ID determinístico baseado no slug (mesma lógica do CheckPermissions)
                $id = $this->generateDeterministicId($route);
                $data[] = [
                    'id' => $id,
                    'name' => $name,
                    'slug' => $route,
                    'description' => $name,
                    'status' => PermissionStatus::Published->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::connection($this->connection)->table($this->getPermissionsTable())->upsert(
                $data,
                ['id'],
                ['name', 'slug', 'description', 'updated_at']
            );
        } catch (\Exception $e) {
            if (app()->hasDebugModeEnabled()) {
                logger()->warning("Erro ao registrar rotas do controller {$controllerClass}: ".$e->getMessage());
            }
        }
    }

    /**
     * Carrega diretórios padrão a partir do config
     */
    protected function loadDefaultDirectories($defaultDirectories = []): void
    {
        // Carrega configuração do arquivo config/raptor.php
        $configuredDirs = []; // config('raptor.route_injector.directories', []);
        // Diretórios padrão se não houver configuração
        // $defaultDirectories = [
        //     'App\\Http\\Controllers\\Tenant' => app_path('Http/Controllers/Tenant'),
        //     // 'Callcocam\\LaravelRaptor\\Http\\Controllers\\Tenant' => __DIR__ . '/../Http/Controllers/Tenant',
        // ];
        $this->controllerDirectories = array_merge($configuredDirs, $defaultDirectories);
    }
}
