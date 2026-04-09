<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Callcocam\LaravelRaptor\Concerns\GeneratesPermissionIds;
use Callcocam\LaravelRaptor\Enums\PermissionStatus;
use Callcocam\LaravelRaptor\Services\PermissionCatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckPermissions extends Command
{
    use GeneratesPermissionIds;

    protected $signature = 'permissions:check
                            {--missing : Show only missing permissions}
                            {--create : Create missing permissions in database}
                            {--update-names : Update names and descriptions of existing permissions}
                            {--only-raptor : Show only Raptor permissions (index, edit, create, execute)}
                            {--context= : Filter by context (tenant or landlord)}';

    protected $description = 'Verifica todas as permissões necessárias baseado nos controllers';

    protected PermissionCatalogService $catalog;

    protected function landlordConnection(): string
    {
        return config('raptor.database.landlord_connection_name', 'landlord');
    }

    public function handle()
    {
        $this->catalog = app(PermissionCatalogService::class);

        $this->info('🔍 Analisando controllers e permissões...');
        $this->newLine();

        $context = $this->option('context');

        // Para CLI: mantém validação de ações dependentes de rota (execute)
        $expectedPermissions = $this->catalog->expectedPermissions($context ?: null, true);

        if ($this->option('only-raptor')) {
            $expectedPermissions = $expectedPermissions->filter(fn ($perm) => $perm['context'] !== null);
        }

        $existingPermissions = $this->getExistingPermissions();
        $expectedSlugs = $expectedPermissions->pluck('slug');

        $missing = $expectedPermissions->filter(function ($perm) use ($existingPermissions) {
            return ! $existingPermissions->contains($perm['slug']);
        });

        $extra = $existingPermissions
            ->diff($expectedSlugs)
            ->reject(fn (string $slug) => $this->catalog->shouldIgnorePermissionSlug($slug))
            ->values();

        if ($this->option('missing')) {
            $this->showMissingPermissions($missing);
        } else {
            $this->showFullReport($expectedPermissions, $existingPermissions, $missing, $extra);
        }

        if ($this->option('create') && $missing->isNotEmpty()) {
            $this->createMissingPermissions($missing);
        }

        if ($this->option('update-names')) {
            $this->updatePermissionNames($expectedPermissions);
        }

        return self::SUCCESS;
    }

    protected function getExistingPermissions(): Collection
    {
        return $this->catalog->getExistingPermissionSlugs($this->landlordConnection());
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

    protected function createMissingPermissions(Collection $missing): void
    {
        if (! $this->confirm('Criar '.$missing->count().' permissões faltantes?', true)) {
            return;
        }

        $permissionModel = config('raptor.shinobi.models.permission');
        if (! class_exists($permissionModel)) {
            $this->error('❌ Modelo de Permission não encontrado: '.$permissionModel);

            return;
        }

        $connection = $this->landlordConnection();
        $table = app($permissionModel)->getTable();
        $columns = $this->getTableColumns($connection, $table);

        $created = 0;

        foreach ($missing as $permission) {
            try {
                if (! $this->catalog->shouldExpectPermission($permission['slug'], $permission['action'], true)) {
                    $this->warn("Pulando {$permission['slug']} (rota não encontrada)");

                    continue;
                }

                $payload = [
                    'id' => $this->generateDeterministicId($permission['slug']),
                    'name' => $permission['name'],
                    'slug' => $permission['slug'],
                    'description' => $permission['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (in_array('context', $columns, true)) {
                    $payload['context'] = $permission['context'];
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

                DB::connection($connection)->table($table)->insert($this->filterPayloadByColumns($payload, $columns));
                $created++;
            } catch (\Exception $e) {
                $this->error("Erro ao criar {$permission['slug']}: {$e->getMessage()}");
            }
        }

        $this->info("✅ {$created} permissões criadas com sucesso!");
    }

    protected function updatePermissionNames(Collection $expectedPermissions): void
    {
        $permissionModel = config('raptor.shinobi.models.permission');
        if (! class_exists($permissionModel)) {
            $this->error('❌ Modelo de Permission não encontrado: '.$permissionModel);

            return;
        }

        $connection = $this->landlordConnection();
        $table = app($permissionModel)->getTable();
        $columns = $this->getTableColumns($connection, $table);
        $updated = 0;

        $this->info('🔄 Atualizando nomes e descrições das permissões...');
        $this->newLine();

        foreach ($expectedPermissions as $permission) {
            $existing = DB::connection($connection)
                ->table($table)
                ->where('slug', $permission['slug'])
                ->first();

            if (! $existing) {
                continue;
            }

            $payload = [
                'name' => $permission['name'],
                'description' => $permission['description'],
                'updated_at' => now(),
            ];

            if (in_array('context', $columns, true)) {
                $payload['context'] = $permission['context'];
            }
            if (in_array('status', $columns, true)) {
                $payload['status'] = PermissionStatus::Published->value;
            }
            if (in_array('deleted_at', $columns, true)) {
                $payload['deleted_at'] = null;
            }

            $payload = $this->filterPayloadByColumns($payload, $columns);

            if (! $this->payloadDiffers($existing, $payload)) {
                continue;
            }

            DB::connection($connection)->table($table)
                ->where('id', $existing->id)
                ->update($payload);

            $this->line("  ✓ <fg=green>{$permission['slug']}</> → {$permission['name']}");
            $updated++;
        }

        $this->newLine();

        if ($updated > 0) {
            $this->info("✅ {$updated} permissões atualizadas com sucesso!");
        } else {
            $this->info('ℹ️  Nenhuma permissão precisou ser atualizada.');
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

            if (data_get($row, $key) != $value) {
                return true;
            }
        }

        return false;
    }
}
