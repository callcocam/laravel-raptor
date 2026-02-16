<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Support\Pages\Execute;
use Callcocam\LaravelRaptor\Support\Pages\Page;
use Callcocam\LaravelRaptor\Support\Pages\Show;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Injeta rotas dinamicamente baseadas em controllers que seguem um padrão específico.
 *
 * Esta classe escaneia diretórios de controllers em busca de classes que implementam
 * o método `getPages()`. A partir das páginas retornadas, ela registra as rotas
 * correspondentes e adiciona rotas complementares (store, update, destroy, etc.).
 *
 * MELHORIAS v2:
 * - Suporte a contextos separados (tenant, landlord)
 * - Cache de controllers descobertos para melhor performance
 * - Configuração separada por contexto
 */
class TenantRouteInjector
{
    /**
     * Configuração de diretórios de controllers para scan.
     * Formato: ['namespace' => 'path']
     *
     * @var array<string, string>
     */
    protected array $controllerDirectories = [];

    protected Filesystem $filesystem;

    protected Router $router;

    /**
     * Contexto atual (tenant, landlord)
     */
    protected ?string $context = null;

    /**
     * @param  array<string, string>  $directories  Diretórios para escanear.
     * @param  string|null  $context  Contexto (tenant, landlord) para carregar da config.
     */
    public function __construct(array $directories = [], ?string $context = null)
    {
        $this->filesystem = new Filesystem;
        $this->router = app('router');
        $this->context = $context;
        $this->loadDirectories($directories, $context);
    }

    /**
     * Cria uma instância para um contexto específico usando a configuração.
     */
    public static function forContext(string $context): self
    {
        return new self([], $context);
    }

    /**
     * Carrega os diretórios baseado nos parâmetros passados ou na configuração.
     * Usa raptor.route_injector.contexts.{context} (separado por contexto, sem filtrar por string).
     */
    protected function loadDirectories(array $directories = [], ?string $context = null): void
    {
        if (! empty($directories)) {
            $this->controllerDirectories = $directories;

            return;
        }

        if ($context) {
            $this->controllerDirectories = config("raptor.route_injector.contexts.{$context}", []);

            return;
        }

        $this->controllerDirectories = config('raptor.route_injector.directories', []);
    }

    /**
     * Adiciona um diretório customizado para o scan de controllers.
     */
    public function addDirectory(string $namespace, string $path): self
    {
        $this->controllerDirectories[$namespace] = $path;

        return $this;
    }

    /**
     * Define um conjunto de diretórios, substituindo os existentes.
     *
     * @param  array<string, string>  $directories
     */
    public function setDirectories(array $directories): self
    {
        $this->controllerDirectories = $directories;

        return $this;
    }

    /**
     * Registra todas as rotas encontradas nos diretórios configurados.
     */
    public function registerRoutes(): void
    {
        $controllers = $this->discoverControllers();

        foreach ($controllers as $controllerClass) {
            $this->registerControllerRoutes($controllerClass);
        }
    }

    /**
     * Descobre todos os controllers válidos, usando cache se habilitado.
     *
     * @return array<int, class-string>
     */
    protected function discoverControllers(): array
    {
        $cacheEnabled = config('raptor.route_injector.cache_enabled', false);

        if (! $cacheEnabled) {
            return $this->scanAllDirectories();
        }

        $cacheKey = $this->getCacheKey();
        $cacheTtl = config('raptor.route_injector.cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTtl, fn () => $this->scanAllDirectories());
    }

    /**
     * Gera a chave de cache baseada nos diretórios e contexto.
     */
    protected function getCacheKey(): string
    {
        $directoriesHash = md5(serialize(array_keys($this->controllerDirectories)));
        $context = $this->context ?? 'global';

        return "raptor.route_injector.controllers.{$context}.{$directoriesHash}";
    }

    /**
     * Escaneia todos os diretórios configurados.
     *
     * @return array<int, class-string>
     */
    protected function scanAllDirectories(): array
    {
        $allControllers = [];

        foreach ($this->controllerDirectories as $namespace => $path) {
            if (! $this->filesystem->isDirectory($path)) {
                continue;
            }

            $controllers = $this->scanControllers($namespace, $path);
            $allControllers = array_merge($allControllers, $controllers);
        }

        return array_unique($allControllers);
    }

    /**
     * Escaneia um diretório em busca de controllers válidos.
     *
     * @return array<int, class-string>
     */
    protected function scanControllers(string $namespace, string $path): array
    {
        $controllers = [];
        $files = $this->filesystem->allFiles($path);

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file, $namespace, $path);

            if ($className && $this->hasGetPagesMethod($className)) {
                $controllers[] = $className;
            }
        }

        return $controllers;
    }

    /**
     * Extrai o nome completo da classe a partir de um arquivo.
     *
     * @return class-string|null
     */
    protected function getClassNameFromFile(\SplFileInfo $file, string $namespace, string $basePath): ?string
    {
        $relativePath = Str::replaceFirst($basePath.'/', '', $file->getPathname());
        $className = Str::of($relativePath)->replace(['/', '.php'], ['\\', ''])->toString();
        $fullClassName = $namespace.'\\'.$className;

        if (class_exists($fullClassName)) {
            return $fullClassName;
        }

        return null;
    }

    /**
     * Verifica se uma classe possui o método público `getPages`.
     *
     * @param  class-string  $className
     */
    protected function hasGetPagesMethod(string $className): bool
    {
        try {
            $reflection = new ReflectionClass($className);

            return $reflection->hasMethod('getPages') && $reflection->getMethod('getPages')->isPublic();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Registra as rotas para um controller específico.
     *
     * @param  class-string  $controllerClass
     */
    protected function registerControllerRoutes(string $controllerClass): void
    {
        try {
            $controller = new $controllerClass;
            $pages = $controller->getPages();

            if (! is_array($pages)) {
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
                logger()->warning("Erro ao registrar rotas do controller {$controllerClass}: ".$e->getMessage());
            }
        }
    }

    /**
     * Adiciona rotas complementares (store, update, destroy, etc.) com base nas páginas existentes.
     *
     * @param  array<string, Page>  $pages
     * @return array<string, Page>
     */
    protected function addComplementaryRoutes(array $pages): array
    {
        $complementary = [];
        $pageKeys = array_keys($pages);

        // Adiciona 'store' se 'create' existir e 'store' não estiver definido
        if (in_array('create', $pageKeys) && ! in_array('store', $pageKeys)) {
            $complementary['store'] = $this->createComplementaryRoute($pages['create'], 'store', 'POST', '/create', 'Criar', 'Salvar');
        }

        // Adiciona 'update' se 'edit' existir e 'update' não estiver definido
        if (in_array('edit', $pageKeys) && ! in_array('update', $pageKeys)) {
            $complementary['update'] = $this->createComplementaryRoute($pages['edit'], 'update', 'PUT', '/edit', 'Editar', 'Atualizar', '');
        }

        // Adiciona rotas de resource se 'index' existir
        if (in_array('index', $pageKeys)) {
            $indexPage = $pages['index'];
            $basePath = $indexPage->getPath();

            if (! in_array('show', $pageKeys)) {
                $showPage = Show::route($basePath.'/{record}');
                $showPage->label = Str::replace('Lista', 'Visualizar', $indexPage->getLabel() ?? '');
                $showPage->name = Str::replace('.index', '.show', $indexPage->getName() ?? '');
                $showPage->middlewares = $indexPage->getMiddlewares();
                $complementary['show'] = $showPage;
            }

            if (! in_array('destroy', $pageKeys)) {
                $complementary['destroy'] = $this->createComplementaryRoute($indexPage, 'destroy', 'DELETE', '', 'Lista', 'Excluir', '/{record}');
            }
            if (! in_array('restore', $pageKeys)) {
                $complementary['restore'] = $this->createComplementaryRoute($indexPage, 'restore', 'POST', '', 'Lista', 'Restaurar', '/{record}/restore');
            }
            if (! in_array('forceDelete', $pageKeys)) {
                $complementary['forceDelete'] = $this->createComplementaryRoute($indexPage, 'forceDelete', 'DELETE', '', 'Lista', 'Excluir Definitivamente', '/{record}/force-delete');
            }
            // Adiciona 'execute' se 'index' existir e 'execute' não estiver definido
            if (! in_array('execute', $pageKeys)) {
                $executePage = Execute::route(sprintf('%s/execute/actions', $basePath));
                $executePage->label = sprintf('Executar %s', $indexPage->getLabel() ?? '');
                $executePage->name = Str::replace('.index', '.execute', $indexPage->getName() ?? '');
                $executePage->middlewares = $indexPage->getMiddlewares();
                $executePage->method = 'POST';
                $complementary['execute'] = $executePage;
            }
        }

        return array_merge($pages, $complementary);
    }

    /**
     * Cria uma rota complementar baseada em uma página existente.
     */
    private function createComplementaryRoute(Page $originalPage, string $action, string $method, string $pathToRemove, string $labelToReplace, string $newLabel, string $pathSuffix = ''): Page
    {
        $newPage = clone $originalPage;
        $basePath = Str::replace($pathToRemove, '', $originalPage->getPath());
        $newPage->path = $basePath.$pathSuffix;
        $newPage->method = $method;
        $newPage->action = $action;
        $newPage->label = Str::replace($labelToReplace, $newLabel, $originalPage->getLabel() ?? '');

        // Gera o nome da rota corretamente
        $originalName = $originalPage->getName() ?? '';
        $baseRouteName = Str::beforeLast($originalName, '.');
        $newPage->name = $baseRouteName.'.'.$action;

        return $newPage;
    }

    /**
     * Registra uma única rota no Laravel.
     * Só registra se o controller tiver o método correspondente à ação.
     *
     * @param  class-string  $controllerClass
     */
    protected function registerRoute(string $controllerClass, string $key, Page $page): void
    {
        if (! $page->isVisible()) {
            return;
        }

        $path = $page->getPath();
        $method = $page->getMethod() ?: 'GET';
        $action = $page->getAction() ?: $key;
        $name = $page->getName() ?: $this->generateRouteName($controllerClass, $key);
        $middlewares = $page->getMiddlewares();

        if (! method_exists($controllerClass, $action)) {
            return;
        }

        $route = $this->router->match([$method], $path, [$controllerClass, $action])->name($name);

        if (! empty($middlewares)) {
            $route->middleware($middlewares);
        }
    }

    /**
     * Gera um nome de rota padrão com base no nome do controller e na ação.
     * Ex: UserController, 'index' -> 'user.index'
     *
     * @param  class-string  $controllerClass
     */
    protected function generateRouteName(string $controllerClass, string $key): string
    {
        $className = class_basename($controllerClass);
        $resourceName = Str::kebab(str_replace('Controller', '', $className));

        return "{$resourceName}.{$key}";
    }
}
