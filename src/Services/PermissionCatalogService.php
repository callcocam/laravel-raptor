<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Concerns\GeneratesPermissionIds;
use Callcocam\LaravelRaptor\Enums\PermissionStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PermissionCatalogService
{
    use GeneratesPermissionIds;

    /**
     * Recursos que não devem ser gerenciados diretamente pelo sync de permissões.
     *
     * @var array<int, string>
     */
    protected array $ignoredResources = [
        'section',
        'sections',
        'shelf',
        'shelves',
        'segment',
        'segments',
        'segement',
        'segements',
        'layer',
        'layers',
        'plannerates',
        'horizon',
        'analysis',
        'execution',
        'executions',
        'verification',
        'cloudflare',
        'api',
        'filepond',
        'profile',
        'profiles',
        'login',
        'logout',
        'password',
        'register',
        'email', 
    ];

    /**
     * Ações cuja permissão deve ser criada somente se a rota nomeada existir.
     *
     * @var array<int, string>
     */
    protected array $routeDependentActions = [
        'execute',
    ];

    /**
     * Ações equivalentes para evitar duplicação de permissões.
     *
     * @var array<string, string>
     */
    protected array $actionAliases = [
        'store' => 'create',
        'update' => 'edit',
        'destroy' => 'delete',
        'execute' => 'create',
        'viewAny' => 'index',
    ];

    /**
     * Ações canônicas esperadas no catálogo de permissões.
     *
     * @var array<int, string>
     */
    protected array $actions = [
        'index',
        'edit',
        'execute',
        'view',
        'create',
        'delete',
        'restore',
        'forceDelete',
    ];

    /**
     * Mapeamento de ações técnicas para nomes amigáveis em português.
     *
     * @var array<string, array{name: string, description: string}>
     */
    protected array $actionLabels = [
        'index' => [
            'name' => 'Listar',
            'description' => 'Permite visualizar a listagem de',
        ],
        'edit' => [
            'name' => 'Editar',
            'description' => 'Permite editar os dados de',
        ],
        'execute' => [
            'name' => 'Executar Ações',
            'description' => 'Permite executar ações especiais em',
        ],
        'view' => [
            'name' => 'Visualizar',
            'description' => 'Permite visualizar os detalhes de',
        ],
        'create' => [
            'name' => 'Criar',
            'description' => 'Permite criar novos registros de',
        ],
        'delete' => [
            'name' => 'Excluir',
            'description' => 'Permite excluir (mover para lixeira)',
        ],
        'restore' => [
            'name' => 'Restaurar',
            'description' => 'Permite restaurar registros excluídos de',
        ],
        'forceDelete' => [
            'name' => 'Excluir Permanentemente',
            'description' => 'Permite excluir permanentemente (sem possibilidade de recuperação)',
        ],
    ];

    /**
     * Mapeamento de recursos para nomes amigáveis em português.
     *
     * @var array<string, string>
     */
    protected array $resourceLabels = [
        'categories' => 'Categorias',
        'clients' => 'Clientes',
        'clusters' => 'Clusters',
        'dashboards' => 'Dashboards',
        'dimensions' => 'Dimensões',
        'gondolas' => 'Gôndolas',
        'images' => 'Imagens',
        'incons' => 'Ícones',
        'settings' => 'Configurações',
        'social-providers' => 'Provedores Sociais',
        'translates' => 'Traduções',
        'providers' => 'Fornecedores',
        'sales' => 'Vendas',
        'workflows' => 'Fluxos',
        'orders' => 'Pedidos',
        'planograms' => 'Planogramas',
        'products' => 'Produtos',
        'product-images' => 'Imagens de Produtos',
        'product-dimensions' => 'Dimensões de Produtos',
        'product-sales' => 'Vendas de Produtos',
        'product-details' => 'Detalhes de Produtos',
        'stores' => 'Lojas',
        'users' => 'Usuários',
        'roles' => 'Perfis',
        'permissions' => 'Permissões',
        'tenants' => 'Empresas',
        'inspirations' => 'Inspirações',
        'addresses' => 'Endereços',
        'mercadologicos' => 'Mercadológicos',
    ];

    /**
     * Retorna permissões esperadas por contexto.
     */
    public function expectedPermissions(?string $contextFilter = null, bool $respectRouteDependencies = false): Collection
    {
        $permissions = collect();
        $controllers = $this->discoverControllersByContext();

        foreach ($controllers as $controller) {
            $context = data_get($controller, 'context');
            if (! is_string($context)) {
                continue;
            }

            if ($contextFilter !== null && $contextFilter !== '' && $context !== $contextFilter) {
                continue;
            }

            $resourceName = $this->getResourceName((string) data_get($controller, 'name'));
            if (! $resourceName || $this->shouldIgnoreResource($resourceName)) {
                continue;
            }

            foreach ($this->actions as $action) {
                $normalizedAction = $this->normalizeAction($action);
                $slug = "{$context}.{$resourceName}.{$normalizedAction}";

                if (! $this->shouldExpectPermission($slug, $normalizedAction, $respectRouteDependencies)) {
                    continue;
                }

                if ($permissions->contains('slug', $slug)) {
                    continue;
                }

                $permissions->push([
                    'slug' => $slug,
                    'name' => $this->getFriendlyName($normalizedAction, $resourceName, $context),
                    'description' => $this->getFriendlyDescription($normalizedAction, $resourceName),
                    'resource' => $resourceName,
                    'action' => $normalizedAction,
                    'context' => $context,
                    'controller' => data_get($controller, 'class'),
                ]);
            }
        }

        return $permissions->values();
    }

    /**
     * Sincroniza permissões esperadas sem apagar extras.
     *
     * @return array{expected:int, created:int, updated:int}
     */
    public function syncPermissionsForConnection(
        string $connection,
        ?string $context = 'tenant',
        bool $respectRouteDependencies = false
    ): array {
        $expected = $this->expectedPermissions($context, $respectRouteDependencies);
        $table = $this->getPermissionsTable();

        if (! $this->tableExists($connection, $table) || $expected->isEmpty()) {
            return [
                'expected' => $expected->count(),
                'created' => 0,
                'updated' => 0,
            ];
        }

        $columns = $this->getTableColumns($connection, $table);
        $expectedSlugs = $expected->pluck('slug')->values()->all();

        $existing = DB::connection($connection)
            ->table($table)
            ->whereIn('slug', $expectedSlugs)
            ->get()
            ->keyBy('slug');

        $created = 0;
        $updated = 0;

        foreach ($expected as $permission) {
            $slug = (string) data_get($permission, 'slug');
            $existingRow = $existing->get($slug);

            if ($existingRow === null) {
                DB::connection($connection)
                    ->table($table)
                    ->insert($this->buildInsertPayload($permission, $columns));
                $created++;

                continue;
            }

            $updatePayload = $this->buildUpdatePayload($permission, $columns);
            if (! $this->payloadDiffers($existingRow, $updatePayload)) {
                continue;
            }

            DB::connection($connection)
                ->table($table)
                ->where('slug', $slug)
                ->update($updatePayload);
            $updated++;
        }

        return [
            'expected' => $expected->count(),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Lista slugs de permissões existentes na conexão informada.
     */
    public function getExistingPermissionSlugs(string $connection, ?string $context = null): Collection
    {
        $table = $this->getPermissionsTable();
        if (! $this->tableExists($connection, $table)) {
            return collect();
        }

        $columns = $this->getTableColumns($connection, $table);
        $query = DB::connection($connection)->table($table);
        if ($this->tableHasColumn($connection, $table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (is_string($context) && $context !== '') {
            if (in_array('context', $columns, true)) {
                $query->where(function ($inner) use ($context) {
                    $inner->where('context', $context)
                        ->orWhere(function ($legacy) use ($context) {
                            $legacy->whereNull('context')
                                ->where('slug', 'like', "{$context}.%");
                        });
                });
            } else {
                $query->where('slug', 'like', "{$context}.%");
            }
        }

        return $query->pluck('slug')
            ->filter(fn ($slug) => is_string($slug) && $slug !== '')
            ->values();
    }

    public function shouldIgnorePermissionSlug(string $slug): bool
    {
        $parts = explode('.', $slug);
        $context = $parts[0] ?? null;

        if (is_string($context) && $context !== '' && $this->shouldIgnoreResource($context)) {
            return true;
        }

        $resource = $this->extractResourceFromSlug($slug);

        return $resource !== null && $this->shouldIgnoreResource($resource);
    }

    public function shouldExpectPermission(string $slug, string $action, bool $respectRouteDependencies = false): bool
    {
        if (! $respectRouteDependencies) {
            return true;
        }

        if (! in_array($action, $this->routeDependentActions, true)) {
            return true;
        }

        return Route::has($slug);
    }

    /**
     * Retorna aliases de ações para normalização canônica.
     *
     * @return array<string, string>
     */
    public function getActionAliases(): array
    {
        return $this->actionAliases;
    }

    /**
     * Normaliza uma ação para sua forma canônica.
     */
    public function canonicalAction(string $action): string
    {
        return $this->normalizeAction($action);
    }

    protected function discoverControllersByContext(): array
    {
        $contexts = config('raptor.route_injector.contexts', []);
        $controllers = [];

        foreach ($contexts as $context => $directories) {
            if (! is_array($directories)) {
                continue;
            }

            foreach ($directories as $namespace => $path) {
                if (! is_string($namespace) || ! is_string($path) || ! File::isDirectory($path)) {
                    continue;
                }

                $files = File::allFiles($path);
                foreach ($files as $file) {
                    $filename = basename($file);
                    if (in_array($filename, ['Controller.php', 'AbstractController.php'], true)) {
                        continue;
                    }

                    $relativePath = str_replace([$path.'/', '.php'], '', $file->getPathname());
                    $className = str_replace('/', '\\', $relativePath);
                    $fullClassName = "{$namespace}\\{$className}";

                    if (! class_exists($fullClassName) || ! $this->isPermissionManagedController($fullClassName)) {
                        continue;
                    }

                    $controllers[] = [
                        'context' => (string) $context,
                        'class' => $fullClassName,
                        'name' => basename($file, '.php'),
                        'path' => $relativePath,
                    ];
                }
            }
        }

        return $controllers;
    }

    protected function isPermissionManagedController(string $controllerClass): bool
    {
        return is_subclass_of($controllerClass, \Callcocam\LaravelRaptor\Http\Controllers\AbstractController::class)
            || is_subclass_of($controllerClass, \Callcocam\LaravelRaptor\Http\Controllers\ResourceController::class);
    }

    protected function shouldIgnoreResource(string $resource): bool
    {
        $normalized = Str::kebab(Str::lower($resource));
        if ($this->isIgnoredToken($normalized)) {
            return true;
        }

        // Cobre casos compostos como "api-sections", "api-shelves", etc.
        $tokens = array_values(array_filter(explode('-', $normalized)));
        foreach ($tokens as $token) {
            if ($this->isIgnoredToken($token)) {
                return true;
            }
        }

        return false;
    }

    protected function isIgnoredToken(string $token): bool
    {
        $token = Str::kebab(Str::lower($token));
        $candidates = [
            $token,
            Str::singular($token),
            Str::plural($token),
        ];

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $this->ignoredResources, true)) {
                return true;
            }
        }

        return false;
    }

    protected function extractResourceFromSlug(string $slug): ?string
    {
        $parts = explode('.', $slug);
        $count = count($parts);

        if ($count >= 3) {
            return $parts[1] ?? null;
        }

        if ($count === 2) {
            return $parts[0] ?? null;
        }

        return null;
    }

    protected function normalizeResourceName(string $resource): string
    {
        return Str::kebab(Str::plural(Str::lower($resource)));
    }

    protected function normalizeAction(string $action): string
    {
        return $this->actionAliases[$action] ?? $action;
    }

    protected function getFriendlyName(string $action, string $resource, ?string $context = null): string
    {
        $actionLabel = data_get($this->actionLabels, "{$action}.name", Str::title($action));
        $resourceLabel = $this->getResourceLabel($resource);
        $contextPrefix = $context === 'landlord' ? 'Admin ' : '';

        return "{$contextPrefix}{$actionLabel} {$resourceLabel}";
    }

    protected function getFriendlyDescription(string $action, string $resource): string
    {
        $actionDescription = data_get($this->actionLabels, "{$action}.description", "Permite {$action} em");
        $resourceLabel = $this->getResourceLabel($resource);

        return "{$actionDescription} {$resourceLabel}";
    }

    protected function getResourceLabel(string $resource): string
    {
        if (isset($this->resourceLabels[$resource])) {
            return $this->resourceLabels[$resource];
        }

        return Str::title(str_replace('-', ' ', $resource));
    }

    protected function getResourceName(string $controllerName): ?string
    {
        $name = str_replace('Controller', '', $controllerName);

        return Str::kebab(Str::plural($name));
    }

    protected function getPermissionsTable(): string
    {
        return config('raptor.shinobi.tables.permissions', 'permissions');
    }

    /**
     * @param  array<string, mixed>  $permission
     * @param  array<int, string>  $columns
     * @return array<string, mixed>
     */
    protected function buildInsertPayload(array $permission, array $columns): array
    {
        $now = now();
        $slug = (string) data_get($permission, 'slug');

        $payload = [
            'id' => $this->generateDeterministicId($slug),
            'name' => data_get($permission, 'name'),
            'slug' => $slug,
            'description' => data_get($permission, 'description'),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (in_array('context', $columns, true)) {
            $payload['context'] = data_get($permission, 'context');
        }
        if (in_array('status', $columns, true)) {
            $payload['status'] = PermissionStatus::Published->value;
        }
        if (in_array('tenant_id', $columns, true)) {
            $payload['tenant_id'] = null;
        }
        if (in_array('deleted_at', $columns, true)) {
            $payload['deleted_at'] = null;
        }

        return $this->filterPayloadByColumns($payload, $columns);
    }

    /**
     * @param  array<string, mixed>  $permission
     * @param  array<int, string>  $columns
     * @return array<string, mixed>
     */
    protected function buildUpdatePayload(array $permission, array $columns): array
    {
        $payload = [
            'name' => data_get($permission, 'name'),
            'description' => data_get($permission, 'description'),
            'updated_at' => now(),
        ];

        if (in_array('context', $columns, true)) {
            $payload['context'] = data_get($permission, 'context');
        }
        if (in_array('status', $columns, true)) {
            $payload['status'] = PermissionStatus::Published->value;
        }
        if (in_array('deleted_at', $columns, true)) {
            $payload['deleted_at'] = null;
        }

        return $this->filterPayloadByColumns($payload, $columns);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $columns
     * @return array<string, mixed>
     */
    protected function filterPayloadByColumns(array $payload, array $columns): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => in_array($key, $columns, true))
            ->all();
    }

    /**
     * @param  object  $row
     * @param  array<string, mixed>  $payload
     */
    protected function payloadDiffers(object $row, array $payload): bool
    {
        foreach ($payload as $key => $value) {
            if ($key === 'updated_at') {
                continue;
            }

            $current = data_get($row, $key);
            if ($current != $value) {
                return true;
            }
        }

        return false;
    }

    protected function tableExists(string $connection, string $table): bool
    {
        try {
            return Schema::connection($connection)->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function getTableColumns(string $connection, string $table): array
    {
        try {
            return Schema::connection($connection)->getColumnListing($table);
        } catch (\Throwable) {
            return [];
        }
    }

    protected function tableHasColumn(string $connection, string $table, string $column): bool
    {
        try {
            return Schema::connection($connection)->hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}
