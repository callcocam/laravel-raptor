<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CheckPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:check 
                            {--missing : Show only missing permissions}
                            {--create : Create missing permissions in database}
                            {--only-raptor : Show only Raptor permissions (index, edit, create, execute)}
                            {--context= : Filter by context (tenant or landlord)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica todas as permissões necessárias baseado nos controllers';

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
            return !$existingPermissions->contains($perm['slug']);
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
            if (!str_contains($relativePath, 'Tenant/') && !str_contains($relativePath, 'Landlord/')) {
                continue;
            }

            // Ignorar controllers base
            if (in_array(basename($file), ['Controller.php', 'AbstractController.php'])) {
                continue;
            }

            $className = str_replace('/', '\\', $relativePath);
            $fullClassName = 'App\\Http\\Controllers\\' . $className;

            if (class_exists($fullClassName)) {
                $controllers[] = [
                    'class' => $fullClassName,
                    'name' => basename($file, '.php'),
                    'path' => $relativePath,
                ];
            }
        }

        return $controllers;
    }

    protected function generateExpectedPermissions($controllers): \Illuminate\Support\Collection
    {
        $permissions = collect();
        
        // Ações padrão CRUD
        $crudActions = ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'];
        
        // Ações extras do Raptor (baseado nas permissões existentes)
        $raptorActions = ['index', 'edit', 'execute'];

        foreach ($controllers as $controller) {
            // Extrair o nome do resource do controller
            $resourceName = $this->getResourceName($controller['name']);
            
            if (!$resourceName) {
                continue;
            }
            
            // Detectar contexto (landlord ou tenant) baseado no namespace
            $context = str_contains($controller['path'], 'Tenant/') ? 'tenant' : 'landlord';

            // Gerar permissões para ações Raptor (usadas na UI)
            foreach ($raptorActions as $action) {
                $slug = "{$context}.{$resourceName}.{$action}";
                $permissions->push([
                    'slug' => $slug,
                    'name' => ucfirst($action) . ' ' . Str::title(str_replace('-', ' ', $resourceName)),
                    'resource' => $resourceName,
                    'action' => $action,
                    'context' => $context,
                    'controller' => $controller['class'],
                ]);
            }
            
            // Gerar permissões CRUD padrão (para policies) também com contexto
            foreach ($crudActions as $action) {
                $slug = "{$context}.{$resourceName}.{$action}";
                $permissions->push([
                    'slug' => $slug,
                    'name' => ucfirst($action) . ' ' . Str::title(str_replace('-', ' ', $resourceName)),
                    'resource' => $resourceName,
                    'action' => $action,
                    'context' => $context,
                    'controller' => $controller['class'],
                ]);
            }
        }

        return $permissions;
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
        
        if (!class_exists($permissionModel)) {
            $this->error('❌ Modelo de Permission não encontrado: ' . $permissionModel);
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
        if (!$this->confirm('Criar ' . $missing->count() . ' permissões faltantes?', true)) {
            return;
        }

        $permissionModel = config('raptor.shinobi.models.permission');
        $created = 0;

        foreach ($missing as $permission) {
            try {
                app($permissionModel)->create([
                    'name' => $permission['name'],
                    'slug' => $permission['slug'],
                    'description' => "Permissão para {$permission['action']} em {$permission['resource']}",
                ]);
                $created++;
            } catch (\Exception $e) {
                $this->error("Erro ao criar {$permission['slug']}: {$e->getMessage()}");
            }
        }

        $this->info("✅ {$created} permissões criadas com sucesso!");
    }
}
