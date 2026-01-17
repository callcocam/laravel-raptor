<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Support\Pages\Page;
use Callcocam\LaravelRaptor\Support\Pages\Show;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;

class TenantRouteInjector
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

    /**
     * Carrega diretórios padrão a partir do config
     */
    protected function loadDefaultDirectories($defaultDirectories = []): void
    {
        // Carrega configuração do arquivo config/raptor.php
        $configuredDirs =[];// config('raptor.route_injector.directories', []); 
        // Diretórios padrão se não houver configuração
        // $defaultDirectories = [
        //     'App\\Http\\Controllers\\Tenant' => app_path('Http/Controllers/Tenant'),
        //     // 'Callcocam\\LaravelRaptor\\Http\\Controllers\\Tenant' => __DIR__ . '/../Http/Controllers/Tenant', 
        // ];
        $this->controllerDirectories = array_merge($configuredDirs, $defaultDirectories);
    }

    /**
     * Adiciona um diretório customizado para scan
     */
    public function addDirectory(string $namespace, string $path): self
    {
        $this->controllerDirectories[$namespace] = $path;
        return $this;
    }

    /**
     * Define diretórios (substitui todos)
     */
    public function setDirectories(array $directories): self
    {
        $this->controllerDirectories = $directories;
        return $this;
    }

    public function registerRoutes(): void
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

            $pages = $this->addComplementaryRoutes($pages);

            foreach ($pages as $key => $page) {
                if ($page instanceof Page) {
                    $this->registerRoute($controllerClass, $key, $page);
                }
            }
        } catch (\Exception $e) {
            if (app()->hasDebugModeEnabled()) {
                logger()->warning("Erro ao registrar rotas do controller {$controllerClass}: " . $e->getMessage());
            }
        }
    }

    protected function addComplementaryRoutes(array $pages): array
    {
        $complementary = [];

        if (isset($pages['create']) && !isset($pages['store'])) {
            $createPage = $pages['create'];
            $storePath = str_replace('/create', '', $createPage->getPath());

            $complementary['store'] = clone $createPage;
            $complementary['store']->path = $storePath;
            $complementary['store']->method = 'POST';
            $complementary['store']->action = 'store';
            $complementary['store']->label = $createPage->getLabel() ? str_replace('Criar', 'Salvar', $createPage->getLabel()) : '';
            $complementary['store']->name = $createPage->getName() ? str_replace('.create', '.store', $createPage->getName()) : '';
        }

        if (isset($pages['edit'])) {
            $editPage = $pages['edit'];
            $updatePath = $editPage->getPath();

            if (!isset($pages['update'])) {
                $updatePath = $editPage->getPath();
                $complementary['update'] = clone $editPage;
                $complementary['update']->path = str($updatePath)
                    ->replace('/edit', '')
                    ->toString();
                $complementary['update']->method = 'PUT';
                $complementary['update']->action = 'update';
                $complementary['update']->label = $editPage->getLabel() ? str_replace('Editar', 'Atualizar', $editPage->getLabel()) : '';
                $complementary['update']->name = $editPage->getName() ? str_replace('.edit', '.update', $editPage->getName()) : '';
            }
        }

        if (isset($pages['index'])) {
            $indexPage = $pages['index'];
            $basePath = $indexPage->getPath();

            if (!isset($pages['show'])) {
                $showPage = Show::route($basePath . '/{record}');
                $showPage->label = $indexPage->getLabel() ? str_replace('Lista', 'Visualizar', $indexPage->getLabel()) : '';
                $showPage->name = $indexPage->getName() ? str_replace('.index', '.show', $indexPage->getName()) : '';
                $showPage->middlewares = $indexPage->getMiddlewares();
                $complementary['show'] = $showPage;
            }

            if (!isset($pages['destroy'])) {
                $complementary['destroy'] = clone $indexPage;
                $complementary['destroy']->path = $basePath . '/{record}';
                $complementary['destroy']->method = 'DELETE';
                $complementary['destroy']->action = 'destroy';
                $complementary['destroy']->label = $indexPage->getLabel() ? str_replace('Lista', 'Excluir', $indexPage->getLabel()) : '';
                $complementary['destroy']->name = $indexPage->getName() ? str_replace('.index', '.destroy', $indexPage->getName()) : '';
            }
        }

        return array_merge($pages, $complementary);
    }

    protected function registerRoute(string $controllerClass, string $key, Page $page): void
    {
        $path = $page->getPath();
        $method = $page->getMethod() ?: 'GET';
        $action = $page->getAction() ?: $key;
        $name = $page->getName() ?: $this->generateRouteName($controllerClass, $key);
        $middlewares = $page->getMiddlewares();

        if (!$page->isVisible()){
            return;
        }
        $route = Route::match(
            [$method],
            $path,
            [$controllerClass, $action]
        )->name($name);

        if (!empty($middlewares)) {
            $route->middleware($middlewares);
        }
    }

    protected function generateRouteName(string $controllerClass, string $key): string
    {
        $className = class_basename($controllerClass);
        $resourceName = str_replace('Controller', '', $className);
        $resourceName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $resourceName));

        return "{$resourceName}.{$key}";
    }
}
