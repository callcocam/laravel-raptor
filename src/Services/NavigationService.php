<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Support\Pages\Index;
use Callcocam\LaravelRaptor\Support\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use ReflectionClass;

class NavigationService
{
    protected array $contexts;
    protected int $cacheTtl;
    protected string $cacheKeyPrefix;

    public function __construct()
    {
        $this->contexts = config('raptor.navigation.contexts', []);
        $this->cacheTtl = config('raptor.navigation.cache_ttl', 3600);
        $this->cacheKeyPrefix = config('raptor.navigation.cache_key_prefix', 'navigation');
    }

    public function buildNavigation(User $user, string $context = 'tenant'): array
    {
        $cacheKey = $this->getCacheKey($user, $context);

        // return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user, $context) {
            return $this->generateNavigation($user, $context);
        // });
    }

    protected function generateNavigation(User $user, string $context): array
    {
        $controllers = $this->scanControllers($context);
        $navigationItems = [];

      
        foreach ($controllers as $controllerClass) {
            $items = $this->processController($controllerClass, $user);
            $navigationItems = array_merge($navigationItems, $items);
        } 
        usort($navigationItems, fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));

        return $navigationItems;
    }

    public function scanControllers(string $context): array
    {
        if (!isset($this->contexts[$context])) {
            return [];
        }

        $config = $this->contexts[$context];
        $controllersPath = $config['controllers_path'];
        $controllersNamespace = $config['controllers_namespace'];

        if (!File::isDirectory($controllersPath)) {
            return [];
        }

        $controllers = [];
        $files = File::allFiles($controllersPath);

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file, $controllersPath, $controllersNamespace);

            if ($className && $this->hasGetPagesMethod($className)) {
                $controllers[] = $className;
            }
        }

        return $controllers;
    }

    protected function getClassNameFromFile($file, string $basePath, string $namespace): ?string
    {
        $relativePath = str_replace($basePath . '/', '', $file->getPathname());
        $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
        $fullClassName = $namespace . '\\' . $className;

        return class_exists($fullClassName) ? $fullClassName : null;
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

    protected function processController(string $controllerClass, User $user): array
    {
        try {
            $controller = new $controllerClass();
            $pages = $controller->getPages();

            if (!is_array($pages)) {
                return [];
            }

            $modelClass = $this->getModelFromController($controller);

            if ($modelClass && !$this->checkPermissions($user, $modelClass)) {
                return [];
            }

            // Filtra apenas as páginas Index para o menu principal
            $indexPages = $this->filterIndexPages($pages);

            $items = [];
            foreach ($indexPages as $page) {
                if ($page->isVisible()) {
                    $item = $this->generateNavigationItem($page, $modelClass);
                    $items[] = $item;
                }
            }

            return $items;

        } catch (\Exception $e) {
            if (app()->hasDebugModeEnabled()) {
                logger()->warning("Erro ao processar controller {$controllerClass}: " . $e->getMessage());
            }
            return [];
        }
    }

    public function filterIndexPages(array $pages): array
    {
        return array_filter($pages, fn($page) => $page instanceof Index);
    }

    public function checkPermissions(User $user, string $modelClass): bool
    {
        try {
            return Gate::forUser($user)->allows('viewAny', $modelClass);
        } catch (\Exception) {
            return config('raptor.navigation.default_permission', true);
        }
    }

    public function generateNavigationItem(Page $page, ?string $modelClass): array
    {
        return [
            'title' => $page->getLabel() ?: $this->generateLabelFromPath($page->getPath()),
            'label' => $page->getLabel() ?: $this->generateLabelFromPath($page->getPath()),
            'href' => $page->getPath(),
            'routeName' => $page->getName(),
            'icon' => $page->getIcon(),
            'group' => $page->getGroup(),
            'groupCollapsible' => $page->isGroupCollapsible(),
            'order' => $page->getOrder(),
            'badge' => $page->getBadge(),
            'isActive' => false,
        ];
    }


    protected function getModelFromController($controller): ?string
    {
        if (method_exists($controller, 'getNavigationModel')) {
            return $controller->getNavigationModel();
        }

        if (method_exists($controller, 'model')) {
            return $controller->model();
        }

        return null;
    }

    protected function generateLabelFromPath(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        $lastSegment = end($segments);
        return str($lastSegment)->title()->toString();
    }

    protected function getCacheKey(User $user, string $context): string
    {
        return "{$this->cacheKeyPrefix}:{$context}:{$user->id}";
    }

    public function clearCache(?User $user = null): void
    {
        if ($user) {
            foreach (array_keys($this->contexts) as $context) {
                Cache::forget($this->getCacheKey($user, $context));
            }
        } else {
            Cache::flush();
        }
    }
}
