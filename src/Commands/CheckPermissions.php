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
                            {--cleanup-redundant : Migrate links and soft-delete redundant alias permissions}
                            {--cleanup-ignored : Soft-delete permissions ignored by the catalog}
                            {--reset : Hard-reset permissions and recreate canonical catalog}
                            {--force : Bypass confirmation prompts for destructive operations}
                            {--only-raptor : Show only Raptor permissions (index, edit, create)}
                            {--context= : Filter by context (tenant or landlord)}';

    protected $description = 'Verifica todas as permissões necessárias baseado nos controllers';

    protected PermissionCatalogService $catalog;
    protected ?string $effectiveContext = null;

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
        $hasMutatingOption = (bool) (
            $this->option('create')
            || $this->option('update-names')
            || $this->option('reset')
            || $this->option('cleanup-redundant')
            || $this->option('cleanup-ignored')
        );
        $effectiveContext = $context ?: ($hasMutatingOption ? 'tenant' : null);
        $this->effectiveContext = $effectiveContext;

        if (! $context && $hasMutatingOption) {
            $this->warn('ℹ️  Sem --context informado em operação de escrita. Usando contexto padrão: tenant');
        }

        if ($hasMutatingOption && $effectiveContext === 'landlord') {
            $this->error('❌ Operações de escrita para contexto landlord estão desabilitadas no momento.');

            return self::FAILURE;
        }

        // Para CLI: mantém validação de ações dependentes de rota (execute)
        $expectedPermissions = $this->catalog->expectedPermissions($effectiveContext, true);

        if ($this->option('reset')) {
            if ($this->option('only-raptor')) {
                $this->error('❌ --reset não pode ser combinado com --only-raptor. Remova --only-raptor.');

                return self::FAILURE;
            }

            $this->resetPermissions($expectedPermissions, $effectiveContext);

            return self::SUCCESS;
        }

        if ($this->option('only-raptor')) {
            $expectedPermissions = $expectedPermissions->filter(fn ($perm) => $perm['context'] !== null);
        }

        $existingPermissions = $this->getExistingPermissions($effectiveContext);
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

        if ($this->option('cleanup-redundant')) {
            $this->cleanupRedundantPermissions($expectedPermissions, $effectiveContext);
        }

        if ($this->option('cleanup-ignored')) {
            $this->cleanupIgnoredPermissions($effectiveContext);
        }

        return self::SUCCESS;
    }

    protected function getExistingPermissions(?string $context = null): Collection
    {
        return $this->catalog->getExistingPermissionSlugs($this->landlordConnection(), $context);
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

    protected function cleanupRedundantPermissions(Collection $expectedPermissions, ?string $context = null): void
    {
        $permissionModel = config('raptor.shinobi.models.permission');
        if (! class_exists($permissionModel)) {
            $this->error('❌ Modelo de Permission não encontrado: '.$permissionModel);

            return;
        }

        $connection = $this->landlordConnection();
        $table = app($permissionModel)->getTable();
        $columns = $this->getTableColumns($connection, $table);

        if (! in_array('deleted_at', $columns, true)) {
            $this->error("❌ A tabela {$table} não suporta soft delete (coluna deleted_at ausente).");

            return;
        }

        $aliases = $this->catalog->getActionAliases();
        if ($aliases === []) {
            $this->info('ℹ️  Nenhum alias configurado para limpeza.');

            return;
        }

        $permissions = DB::connection($connection)
            ->table($table)
            ->whereNull('deleted_at');

        $this->applyContextFilter($permissions, $context, $columns);

        $permissions = $permissions->get();

        $redundant = $permissions
            ->map(function (object $permission) use ($aliases) {
                $slug = (string) data_get($permission, 'slug');
                $parts = $this->parsePermissionSlug($slug);

                if ($parts === null) {
                    return null;
                }

                $canonicalAction = $aliases[$parts['action']] ?? null;
                if (! is_string($canonicalAction) || $canonicalAction === $parts['action']) {
                    return null;
                }

                return [
                    'id' => (string) data_get($permission, 'id'),
                    'slug' => $slug,
                    'prefix' => $parts['prefix'],
                    'action' => $parts['action'],
                    'canonical_action' => $canonicalAction,
                    'canonical_slug' => "{$parts['prefix']}.{$canonicalAction}",
                    'row' => $permission,
                ];
            })
            ->filter()
            ->values();

        if ($redundant->isEmpty()) {
            $this->info('✅ Nenhuma permissão redundante encontrada.');

            return;
        }

        $this->warn("🧹 Limpando {$redundant->count()} permissões redundantes...");

        $expectedBySlug = $expectedPermissions->keyBy('slug');
        $permissionRoleTable = config('raptor.shinobi.tables.permission_role', config('raptor.tables.permission_role', 'permission_role'));
        $permissionUserTable = config('raptor.shinobi.tables.permission_user', config('raptor.tables.permission_user', 'permission_user'));

        $summary = [
            'canonical_created' => 0,
            'canonical_restored' => 0,
            'role_links_migrated' => 0,
            'user_links_migrated' => 0,
            'permissions_inactivated' => 0,
        ];

        foreach ($redundant as $item) {
            $canonicalSlug = (string) data_get($item, 'canonical_slug');
            $canonical = DB::connection($connection)->table($table)->where('slug', $canonicalSlug)->first();
            $canonicalId = null;

            if ($canonical === null) {
                $expected = $expectedBySlug->get($canonicalSlug, []);
                $existingRow = data_get($item, 'row');
                $now = now();

                $context = data_get($expected, 'context');
                if ($context === null) {
                    $context = data_get($existingRow, 'context');
                }
                if ($context === null) {
                    $context = explode('.', (string) data_get($item, 'prefix'))[0] ?? null;
                }

                $payload = [
                    'id' => $this->generateDeterministicId($canonicalSlug),
                    'name' => data_get($expected, 'name', data_get($existingRow, 'name', $canonicalSlug)),
                    'slug' => $canonicalSlug,
                    'description' => data_get($expected, 'description', data_get($existingRow, 'description')),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];

                if (in_array('context', $columns, true)) {
                    $payload['context'] = $context;
                }
                if (in_array('status', $columns, true)) {
                    $payload['status'] = PermissionStatus::Published->value;
                }
                if (in_array('tenant_id', $columns, true)) {
                    $payload['tenant_id'] = data_get($existingRow, 'tenant_id');
                }

                DB::connection($connection)->table($table)->insert($this->filterPayloadByColumns($payload, $columns));
                $canonicalId = $payload['id'];
                $summary['canonical_created']++;
            } else {
                $canonicalId = (string) data_get($canonical, 'id');

                if (data_get($canonical, 'deleted_at') !== null) {
                    $restorePayload = ['deleted_at' => null, 'updated_at' => now()];
                    if (in_array('status', $columns, true)) {
                        $restorePayload['status'] = PermissionStatus::Published->value;
                    }

                    DB::connection($connection)->table($table)
                        ->where('id', $canonicalId)
                        ->update($this->filterPayloadByColumns($restorePayload, $columns));
                    $summary['canonical_restored']++;
                }
            }

            $sourcePermissionId = (string) data_get($item, 'id');
            $summary['role_links_migrated'] += $this->migratePermissionLinks(
                $connection,
                $permissionRoleTable,
                'role_id',
                $sourcePermissionId,
                $canonicalId
            );
            $summary['user_links_migrated'] += $this->migratePermissionLinks(
                $connection,
                $permissionUserTable,
                'user_id',
                $sourcePermissionId,
                $canonicalId
            );

            if ($this->tableExists($connection, $permissionRoleTable)) {
                DB::connection($connection)->table($permissionRoleTable)
                    ->where('permission_id', $sourcePermissionId)
                    ->delete();
            }
            if ($this->tableExists($connection, $permissionUserTable)) {
                DB::connection($connection)->table($permissionUserTable)
                    ->where('permission_id', $sourcePermissionId)
                    ->delete();
            }

            DB::connection($connection)->table($table)
                ->where('id', $sourcePermissionId)
                ->update($this->filterPayloadByColumns([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ], $columns));

            $summary['permissions_inactivated']++;
            $this->line("  ✓ {$item['slug']} → {$item['canonical_slug']}");
        }

        $this->newLine();
        $this->info('✅ Limpeza concluída');
        $this->line("   • Canônicas criadas: {$summary['canonical_created']}");
        $this->line("   • Canônicas restauradas: {$summary['canonical_restored']}");
        $this->line("   • Vínculos role migrados: {$summary['role_links_migrated']}");
        $this->line("   • Vínculos user migrados: {$summary['user_links_migrated']}");
        $this->line("   • Redundantes inativadas: {$summary['permissions_inactivated']}");
    }

    protected function cleanupIgnoredPermissions(?string $context = null): void
    {
        $permissionModel = config('raptor.shinobi.models.permission');
        if (! class_exists($permissionModel)) {
            $this->error('❌ Modelo de Permission não encontrado: '.$permissionModel);

            return;
        }

        $connection = $this->landlordConnection();
        $table = app($permissionModel)->getTable();
        $columns = $this->getTableColumns($connection, $table);

        if (! in_array('deleted_at', $columns, true)) {
            $this->error("❌ A tabela {$table} não suporta soft delete (coluna deleted_at ausente).");

            return;
        }

        $permissionRoleTable = config('raptor.shinobi.tables.permission_role', config('raptor.tables.permission_role', 'permission_role'));
        $permissionUserTable = config('raptor.shinobi.tables.permission_user', config('raptor.tables.permission_user', 'permission_user'));

        $ignoredQuery = DB::connection($connection)->table($table)
            ->whereNull('deleted_at');

        $this->applyContextFilter($ignoredQuery, $context, $columns);

        $ignored = $ignoredQuery->get()
            ->filter(fn (object $row) => $this->catalog->shouldIgnorePermissionSlug((string) data_get($row, 'slug')))
            ->values();

        if ($ignored->isEmpty()) {
            $this->info('✅ Nenhuma permissão ignorada para limpar.');

            return;
        }

        $this->warn("🧹 Limpando {$ignored->count()} permissões ignoradas...");

        $removedRoleLinks = 0;
        $removedUserLinks = 0;
        $inactivated = 0;

        foreach ($ignored as $permission) {
            $permissionId = (string) data_get($permission, 'id');

            if ($this->tableExists($connection, $permissionRoleTable)) {
                $removedRoleLinks += DB::connection($connection)->table($permissionRoleTable)
                    ->where('permission_id', $permissionId)
                    ->delete();
            }

            if ($this->tableExists($connection, $permissionUserTable)) {
                $removedUserLinks += DB::connection($connection)->table($permissionUserTable)
                    ->where('permission_id', $permissionId)
                    ->delete();
            }

            DB::connection($connection)->table($table)
                ->where('id', $permissionId)
                ->update($this->filterPayloadByColumns([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ], $columns));

            $inactivated++;
            $this->line("  ✓ ".data_get($permission, 'slug'));
        }

        $this->newLine();
        $this->info('✅ Limpeza de ignoradas concluída');
        $this->line("   • Vínculos role removidos: {$removedRoleLinks}");
        $this->line("   • Vínculos user removidos: {$removedUserLinks}");
        $this->line("   • Ignoradas inativadas: {$inactivated}");
    }

    protected function resetPermissions(Collection $expectedPermissions, ?string $context = null): void
    {
        $permissionModel = config('raptor.shinobi.models.permission');
        if (! class_exists($permissionModel)) {
            $this->error('❌ Modelo de Permission não encontrado: '.$permissionModel);

            return;
        }

        $connection = $this->landlordConnection();
        $table = app($permissionModel)->getTable();
        $columns = $this->getTableColumns($connection, $table);
        $permissionRoleTable = config('raptor.shinobi.tables.permission_role', config('raptor.tables.permission_role', 'permission_role'));
        $permissionUserTable = config('raptor.shinobi.tables.permission_user', config('raptor.tables.permission_user', 'permission_user'));

        $selectionQuery = DB::connection($connection)->table($table);
        $this->applyContextFilter($selectionQuery, $context, $columns);

        $toReset = $selectionQuery->get(['id', 'slug']);
        $toResetIds = $toReset->pluck('id')->filter()->values();

        if ($toReset->isEmpty()) {
            $this->warn('ℹ️  Nenhuma permissão encontrada para reset no filtro atual.');
        }

        $targetLabel = $context ? "contexto {$context}" : 'todos os contextos';
        $message = sprintf(
            '⚠️  Resetar permissões (%s): apagar %d permissões e recriar %d canônicas. Continuar?',
            $targetLabel,
            $toReset->count(),
            $expectedPermissions->count()
        );

        if (! $this->option('force') && ! $this->confirm($message, false)) {
            $this->warn('Reset cancelado.');

            return;
        }

        $stats = [
            'role_links_removed' => 0,
            'user_links_removed' => 0,
            'permissions_deleted' => 0,
            'permissions_created' => 0,
        ];

        DB::connection($connection)->transaction(function () use (
            $connection,
            $table,
            $columns,
            $permissionRoleTable,
            $permissionUserTable,
            $toResetIds,
            $expectedPermissions,
            &$stats
        ) {
            if ($toResetIds->isNotEmpty()) {
                if ($this->tableExists($connection, $permissionRoleTable)) {
                    $stats['role_links_removed'] = DB::connection($connection)->table($permissionRoleTable)
                        ->whereIn('permission_id', $toResetIds->all())
                        ->delete();
                }
                if ($this->tableExists($connection, $permissionUserTable)) {
                    $stats['user_links_removed'] = DB::connection($connection)->table($permissionUserTable)
                        ->whereIn('permission_id', $toResetIds->all())
                        ->delete();
                }

                $stats['permissions_deleted'] = DB::connection($connection)->table($table)
                    ->whereIn('id', $toResetIds->all())
                    ->delete();
            }

            foreach ($expectedPermissions as $permission) {
                $slug = (string) data_get($permission, 'slug');
                if ($slug === '') {
                    continue;
                }

                $payload = [
                    'id' => $this->generateDeterministicId($slug),
                    'name' => data_get($permission, 'name'),
                    'slug' => $slug,
                    'description' => data_get($permission, 'description'),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
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

                DB::connection($connection)->table($table)
                    ->insert($this->filterPayloadByColumns($payload, $columns));
                $stats['permissions_created']++;
            }
        });

        $this->newLine();
        $this->info('✅ Reset concluído');
        $this->line("   • Vínculos role removidos: {$stats['role_links_removed']}");
        $this->line("   • Vínculos user removidos: {$stats['user_links_removed']}");
        $this->line("   • Permissões apagadas: {$stats['permissions_deleted']}");
        $this->line("   • Permissões recriadas: {$stats['permissions_created']}");
    }

    protected function migratePermissionLinks(
        string $connection,
        string $pivotTable,
        string $relatedColumn,
        string $sourcePermissionId,
        string $targetPermissionId
    ): int {
        if ($sourcePermissionId === $targetPermissionId || ! $this->tableExists($connection, $pivotTable)) {
            return 0;
        }

        $columns = $this->getTableColumns($connection, $pivotTable);
        if (! in_array('permission_id', $columns, true) || ! in_array($relatedColumn, $columns, true)) {
            return 0;
        }

        $rows = DB::connection($connection)->table($pivotTable)
            ->where('permission_id', $sourcePermissionId)
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $migrated = 0;

        foreach ($rows as $row) {
            $payload = [
                'permission_id' => $targetPermissionId,
                $relatedColumn => data_get($row, $relatedColumn),
            ];

            $now = now();
            if (in_array('created_at', $columns, true)) {
                $payload['created_at'] = $now;
            }
            if (in_array('updated_at', $columns, true)) {
                $payload['updated_at'] = $now;
            }

            $migrated += DB::connection($connection)->table($pivotTable)
                ->insertOrIgnore($this->filterPayloadByColumns($payload, $columns));
        }

        return $migrated;
    }

    /**
     * @return array{prefix:string,action:string}|null
     */
    protected function parsePermissionSlug(string $slug): ?array
    {
        $parts = explode('.', $slug);
        if (count($parts) < 2) {
            return null;
        }

        $action = (string) array_pop($parts);
        $prefix = implode('.', $parts);

        if ($action === '' || $prefix === '') {
            return null;
        }

        return [
            'prefix' => $prefix,
            'action' => $action,
        ];
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

    protected function tableExists(string $connection, string $table): bool
    {
        try {
            return Schema::connection($connection)->hasTable($table);
        } catch (\Throwable) {
            return false;
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

    protected function applyContextFilter($query, ?string $context, array $columns): void
    {
        if (! is_string($context) || $context === '') {
            return;
        }

        if (in_array('context', $columns, true)) {
            $query->where(function ($inner) use ($context) {
                $inner->where('context', $context)
                    ->orWhere(function ($legacy) use ($context) {
                        $legacy->whereNull('context')
                            ->where('slug', 'like', "{$context}.%");
                    });
            });

            return;
        }

        $query->where('slug', 'like', "{$context}.%");
    }
}
