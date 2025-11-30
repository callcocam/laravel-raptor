<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Enums\PermissionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class PermissionGenerator
{
    /**
     * Configuração de diretórios de controllers para scan
     * Formato: ['namespace' => 'path']
     */
    protected array $controllerDirectories = [];

    public function __construct(array $defaultDirectories = [])
    {
        $this->loadDefaultDirectories($defaultDirectories);
    }

    public static function generate(array $defaultDirectories = []): self
    {
        return new self($defaultDirectories);
    }

    public function save($drop = false): void
    {
        if ($drop) {
            DB::table('permissions')->delete();
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
            if (!File::isDirectory($path)) {
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
        $relativePath = str_replace($basePath . '/', '', $file->getPathname());
        $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
        $fullClassName = $namespace . '\\' . $className;

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
            $controller = new $controllerClass();
            $pages = $controller->getPages();

            if (!is_array($pages)) {
                return;
            }
            $context = 'tenant';
            if (!str($controllerClass)->contains('Tenant')) {
                $context = 'landlord';
            }
            $data = [];
            foreach ($pages as $page) {
                // Lógica para registrar permissões com base nas páginas
                // Exemplo: Permission::firstOrCreate(['name' => $page['name'], 'route' => $page['route']]);
                $name = sprintf('%s - (%s)', $page->getLabel(), $page->getAction());
                $route = sprintf('%s.%s', $context, $page->getName());
                // Gera um ID único baseado no nome da rota (sempre o mesmo para a mesma rota)
                $id = substr(hash('sha256', $route), 0, 26);
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

            DB::table('permissions')->upsert($data, ['id'], ['name', 'slug', 'updated_at']);
        } catch (\Exception $e) {
            dd($e);
            if (app()->hasDebugModeEnabled()) {
                logger()->warning("Erro ao registrar rotas do controller {$controllerClass}: " . $e->getMessage());
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
