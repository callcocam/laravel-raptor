<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Callcocam\LaravelRaptor\Concerns\GeneratesPermissionIds;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CheckPermissions extends Command
{
    use GeneratesPermissionIds;

    /**
     * Ações cuja permissão deve ser criada somente se a rota nomeada existir.
     *
     * @var array<int, string>
     */
    protected array $routeDependentActions = [
        'create',
        'execute',
    ];

    /**
     * Ações equivalentes para evitar duplicação de permissões.
     *
     * store -> create
     * update -> edit
     * viewAny -> index
     *
     * @var array<string, string>
     */
    protected array $actionAliases = [
        'store' => 'create',
        'update' => 'edit',
        'viewAny' => 'index',
    ];

    protected function landlordConnection(): string
    {
        return config('raptor.database.landlord_connection_name', 'landlord');
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:check 
                            {--missing : Show only missing permissions}
                            {--create : Create missing permissions in database}
                            {--update-names : Update names and descriptions of existing permissions}
                            {--only-raptor : Show only Raptor permissions (index, edit, create, execute)}
                            {--context= : Filter by context (tenant or landlord)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica todas as permissões necessárias baseado nos controllers';

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
        'viewAny' => [
            'name' => 'Ver Todos',
            'description' => 'Permite visualizar todos os registros de',
        ],
        'view' => [
            'name' => 'Visualizar',
            'description' => 'Permite visualizar os detalhes de',
        ],
        'create' => [
            'name' => 'Criar',
            'description' => 'Permite criar novos registros de',
        ],
        'update' => [
            'name' => 'Atualizar',
            'description' => 'Permite atualizar os dados de',
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
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Analisando controllers e permissões...');
        $this->newLine();

        // Buscar todos os controllers
        $controllers = $this->getControllers();

        // Gerar permissões esperadas
        $expectedPermissions = $this->generateExpectedPermissions($controllers);

        // Filtrar por tipo se solicitado
        if ($this->option('only-raptor')) {
            $expectedPermissions = $expectedPermissions->filter(function ($perm) {
                return $perm['context'] !== null; // Apenas permissões com contexto (tenant/landlord)
            });
        }

        // Filtrar por contexto se solicitado
        if ($context = $this->option('context')) {
            $expectedPermissions = $expectedPermissions->filter(function ($perm) use ($context) {
                return $perm['context'] === $context;
            });
        }

        // Buscar permissões existentes no banco
        $existingPermissions = $this->getExistingPermissions();

        // Extrair apenas os slugs para comparação
        $expectedSlugs = $expectedPermissions->pluck('slug');

        // Comparar
        $missing = $expectedPermissions->filter(function ($perm) use ($existingPermissions) {
            return ! $existingPermissions->contains($perm['slug']);
        });

        $extra = $existingPermissions->diff($expectedSlugs);

        // Mostrar resultados
        if ($this->option('missing')) {
            $this->showMissingPermissions($missing);
        } else {
            $this->showFullReport($expectedPermissions, $existingPermissions, $missing, $extra);
        }

        // Criar permissões faltantes se solicitado
        if ($this->option('create') && $missing->isNotEmpty()) {
            $this->createMissingPermissions($missing);
        }

        // Atualizar nomes e descrições se solicitado
        if ($this->option('update-names')) {
            $this->updatePermissionNames($expectedPermissions);
        }

        return self::SUCCESS;
    }

    protected function getControllers(): array
    {
        $controllersPath = app_path('Http/Controllers');
        $controllers = [];

        $files = File::allFiles($controllersPath);

        foreach ($files as $file) {
            $relativePath = str_replace(
                [app_path('Http/Controllers/'), '.php'],
                '',
                $file->getPathname()
            );

            // Ignorar controllers que não estão em Tenant/ ou Landlord/
            if (! str_contains($relativePath, 'Tenant/') && ! str_contains($relativePath, 'Landlord/')) {
                continue;
            }

            // Ignorar controllers base
            if (in_array(basename($file), ['Controller.php', 'AbstractController.php'])) {
                continue;
            }

            $className = str_replace('/', '\\', $relativePath);
            $fullClassName = 'App\\Http\\Controllers\\'.$className;

            if (class_exists($fullClassName)) {
                if (! $this->isPermissionManagedController($fullClassName)) {
                    continue;
                }

                $controllers[] = [
                    'class' => $fullClassName,
                    'name' => basename($file, '.php'),
                    'path' => $relativePath,
                ];
            }
        }

        return $controllers;
    }

    protected function isPermissionManagedController(string $controllerClass): bool
    {
        return is_subclass_of($controllerClass, \Callcocam\LaravelRaptor\Http\Controllers\AbstractController::class)
            || is_subclass_of($controllerClass, \Callcocam\LaravelRaptor\Http\Controllers\ResourceController::class);
    }

    protected function generateExpectedPermissions($controllers): \Illuminate\Support\Collection
    {

        if ($this->confirm('Deseja resetar as permissões existentes antes de criar as faltantes?')) {
            $permissionModel = config('raptor.shinobi.models.permission');

            // Remove todas as relações permission_role e permission_user primeiro (landlord)
            $conn = $this->landlordConnection();
            DB::connection($conn)->table('permission_role')->delete();
            DB::connection($conn)->table('permission_user')->delete();

            // Remove todas as permissions usando delete() para respeitar foreign keys
            app($permissionModel)->query()->forceDelete();

            $this->info('🗑️ Permissões e suas relações foram removidas.');
        }

        $permissions = collect();

        // Todas as ações necessárias (sem duplicatas)
        $actions = [
            // Ações da UI (Raptor)
            'index',
            'edit',
            // 'store',
            'execute',
            // Ações CRUD (Policies)
            'viewAny',
            'view',
            'create',
            // 'update',
            'delete',
            'restore',
            'forceDelete',
        ];

        foreach ($controllers as $controller) {
            // Extrair o nome do resource do controller
            $resourceName = $this->getResourceName($controller['name']);

            if (! $resourceName) {
                continue;
            }

            // Detectar contexto (landlord ou tenant) baseado no namespace
            $context = str_contains($controller['path'], 'Tenant/') ? 'tenant' : 'landlord';

            // Gerar permissões para todas as ações
            foreach ($actions as $action) {
                $normalizedAction = $this->normalizeAction($action);
                $slug = "{$context}.{$resourceName}.{$normalizedAction}";

                if (! $this->shouldExpectPermission($slug, $normalizedAction)) {
                    continue;
                }

                // Evita duplicatas
                if (! $permissions->contains('slug', $slug)) {
                    $permissions->push([
                        'slug' => $slug,
                        'name' => $this->getFriendlyName($normalizedAction, $resourceName),
                        'description' => $this->getFriendlyDescription($normalizedAction, $resourceName),
                        'resource' => $resourceName,
                        'action' => $normalizedAction,
                        'context' => $context,
                        'controller' => $controller['class'],
                    ]);
                }
            }
        }

        return $permissions;
    }

    protected function shouldExpectPermission(string $slug, string $action): bool
    {
        if (! in_array($action, $this->routeDependentActions, true)) {
            return true;
        }

        return Route::has($slug);
    }

    protected function normalizeAction(string $action): string
    {
        return $this->actionAliases[$action] ?? $action;
    }

    /**
     * Retorna um nome amigável para a permissão.
     */
    protected function getFriendlyName(string $action, string $resource): string
    {
        $actionLabel = $this->actionLabels[$action]['name'] ?? Str::title($action);
        $resourceLabel = $this->getResourceLabel($resource);

        return "{$actionLabel} {$resourceLabel}";
    }

    /**
     * Retorna uma descrição amigável para a permissão.
     */
    protected function getFriendlyDescription(string $action, string $resource): string
    {
        $actionDescription = $this->actionLabels[$action]['description'] ?? "Permite {$action} em";
        $resourceLabel = $this->getResourceLabel($resource);

        return "{$actionDescription} {$resourceLabel}";
    }

    /**
     * Retorna o label amigável do recurso ou formata automaticamente.
     */
    protected function getResourceLabel(string $resource): string
    {
        // Se temos um label definido, usa ele
        if (isset($this->resourceLabels[$resource])) {
            return $this->resourceLabels[$resource];
        }

        // Caso contrário, formata automaticamente: kebab-case -> Title Case
        return Str::title(str_replace('-', ' ', $resource));
    }

    protected function getResourceName(string $controllerName): ?string
    {
        // Remove 'Controller' do final
        $name = str_replace('Controller', '', $controllerName);

        // Converte para plural e kebab-case
        $plural = Str::plural($name);
        $kebab = Str::kebab($plural);

        return $kebab;
    }

    protected function getExistingPermissions(): \Illuminate\Support\Collection
    {
        $permissionModel = config('raptor.shinobi.models.permission');

        if (! class_exists($permissionModel)) {
            $this->error('❌ Modelo de Permission não encontrado: '.$permissionModel);

            return collect();
        }

        return app($permissionModel)
            ->get()
            ->pluck('slug');
    }

    protected function showFullReport($expected, $existing, $missing, $extra): void
    {
        $this->info('📊 RELATÓRIO DE PERMISSÕES');
        $this->newLine();

        $this->info("✅ Total esperado: {$expected->count()}");
        $this->info("📦 Total existente: {$existing->count()}");
        $this->warn("⚠️  Faltando: {$missing->count()}");
        $this->error("❌ Extras (não mapeadas): {$extra->count()}");
        $this->newLine();

        if ($missing->isNotEmpty()) {
            $this->warn('🔴 PERMISSÕES FALTANDO:');
            $this->newLine();

            $grouped = $missing->groupBy('resource');

            foreach ($grouped as $resource => $perms) {
                $this->line("  <fg=yellow>📁 {$resource}</>");
                foreach ($perms as $perm) {
                    $this->line("     • {$perm['slug']} - {$perm['name']}");
                }
                $this->newLine();
            }

            $this->info('💡 Para criar as permissões faltantes, execute:');
            $this->line('   <fg=green>php artisan permissions:check --create</>');
            $this->newLine();
        }

        if ($extra->isNotEmpty()) {
            $this->error('🔵 PERMISSÕES EXTRAS (não mapeadas para controllers):');
            foreach ($extra as $slug) {
                $this->line("  • {$slug}");
            }
            $this->newLine();
        }
    }

    protected function showMissingPermissions($missing): void
    {
        if ($missing->isEmpty()) {
            $this->info('✅ Todas as permissões estão definidas!');

            return;
        }

        $this->warn("🔴 {$missing->count()} PERMISSÕES FALTANDO:");
        $this->newLine();

        $grouped = $missing->groupBy('resource');

        foreach ($grouped as $resource => $perms) {
            $this->line("<fg=yellow>📁 {$resource}</>");
            foreach ($perms as $perm) {
                $this->line("   • {$perm['slug']}");
            }
            $this->newLine();
        }
    }

    protected function createMissingPermissions($missing): void
    {
        if (! $this->confirm('Criar '.$missing->count().' permissões faltantes?', true)) {
            return;
        }

        $permissionModel = config('raptor.shinobi.models.permission');
        $table = app($permissionModel)->getTable();
        $created = 0;

        foreach ($missing as $permission) {
            try {
                if (! $this->shouldCreatePermission($permission)) {
                    $this->warn("Pulando {$permission['slug']} (rota não encontrada)");

                    continue;
                }

                $id = $this->generateDeterministicId($permission['slug']);

                // Usa DB::table() na conexão landlord para inserir com ID específico
                DB::connection($this->landlordConnection())->table($table)->insert([
                    'id' => $id,
                    'name' => $permission['name'],
                    'slug' => $permission['slug'],
                    'description' => $permission['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
            } catch (\Exception $e) {
                $this->error("Erro ao criar {$permission['slug']}: {$e->getMessage()}");
            }
        }

        $this->info("✅ {$created} permissões criadas com sucesso!");
    }

    /**
     * Decide se a permissão pode ser criada.
     * Para ações de UI, exige que a rota nomeada exista.
     * Para ações de policy, mantém criação sem depender de rota.
     *
     * @param  array{slug:string, action:string}  $permission
     */
    protected function shouldCreatePermission(array $permission): bool
    {
        if (! in_array($permission['action'], $this->routeDependentActions, true)) {
            return true;
        }

        return Route::has($permission['slug']);
    }

    /**
     * Atualiza os nomes e descrições das permissões existentes.
     *
     * IMPORTANTE: Usa query builder direto para evitar que o HasSlug trait
     * sobrescreva a slug ao atualizar o name.
     */
    protected function updatePermissionNames(\Illuminate\Support\Collection $expectedPermissions): void
    {
        $permissionModel = config('raptor.shinobi.models.permission');
        $table = app($permissionModel)->getTable();
        $updated = 0;

        $this->info('🔄 Atualizando nomes e descrições das permissões...');
        $this->newLine();

        foreach ($expectedPermissions as $permission) {
            $existing = app($permissionModel)->where('slug', $permission['slug'])->first();

            if (! $existing) {
                continue;
            }

            // Só atualiza se o nome ou descrição forem diferentes
            if ($existing->name !== $permission['name'] || $existing->description !== $permission['description']) {
                // Usa query builder na conexão landlord para evitar que o HasSlug trait sobrescreva a slug
                DB::connection($this->landlordConnection())->table($table)
                    ->where('id', $existing->id)
                    ->update([
                        'name' => $permission['name'],
                        'description' => $permission['description'],
                        'updated_at' => now(),
                    ]);

                $this->line("  ✓ <fg=green>{$permission['slug']}</> → {$permission['name']}");
                $updated++;
            }
        }

        $this->newLine();

        if ($updated > 0) {
            $this->info("✅ {$updated} permissões atualizadas com sucesso!");
        } else {
            $this->info('ℹ️  Nenhuma permissão precisou ser atualizada.');
        }
    }
}
