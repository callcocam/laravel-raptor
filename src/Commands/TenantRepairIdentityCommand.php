<?php

namespace Callcocam\LaravelRaptor\Commands;

use Callcocam\LaravelRaptor\Services\TenantDatabaseManager;
use Illuminate\Console\Command;

class TenantRepairIdentityCommand extends Command
{
    protected $signature = 'tenant:repair-identity
                            {--apply : Aplica as correções detectadas (padrão é dry-run)}
                            {--dry-run : Simula sem persistir alterações}
                            {--tenant= : ID do tenant específico}
                            {--database= : Nome do banco dedicado para filtrar}';

    protected $description = 'Verifica e corrige inconsistências de identidade (id divergente por slug) entre landlord e banco dedicado do tenant';

    public function __construct(protected TenantDatabaseManager $tenantDatabaseManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $dryRun = ! $apply;

        if ($apply && $this->option('dry-run')) {
            $this->warn('As opções --apply e --dry-run foram informadas. O comando seguirá com --apply.');
        }

        $tenantClass = config('raptor.landlord.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $tenantId = $this->option('tenant');
        $databaseFilter = $this->option('database');

        $query = $tenantClass::query();

        if (! empty($tenantId)) {
            $query->whereKey($tenantId);
        }

        if (! empty($databaseFilter)) {
            $query->where('database', $databaseFilter);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('Nenhum tenant encontrado para os filtros informados.');

            return self::SUCCESS;
        }

        $mode = $dryRun ? 'DRY-RUN (sem alterações)' : 'APPLY (corrigindo inconsistências)';
        $this->info("Modo: {$mode}");
        $this->line('');

        $stats = [
            'checked' => 0,
            'fixed' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $landlordDatabase = $this->tenantDatabaseManager->getLandlordDatabaseName();
        $restoreDatabase = $this->tenantDatabaseManager->getDefaultDatabaseName();

        try {
            foreach ($tenants as $tenant) {
                $tenantDatabase = (string) ($tenant->getAttribute('database') ?? '');
                $tenantLabel = sprintf('%s (%s)', (string) $tenant->getKey(), (string) ($tenant->getAttribute('slug') ?? '-'));

                if (! $this->tenantDatabaseManager->isDedicatedTenantDatabase($tenantDatabase)) {
                    $stats['skipped']++;
                    $this->line(sprintf(
                        '[SKIPPED] %s - banco dedicado ausente ou igual ao landlord (%s).',
                        $tenantLabel,
                        $tenantDatabase !== '' ? $tenantDatabase : $landlordDatabase
                    ));

                    continue;
                }

                $stats['checked']++;

                try {
                    $identity = $this->tenantDatabaseManager->inspectTenantIdentity($tenant, $tenantDatabase);
                    $conflictingId = $identity['slug_conflict_id'];

                    if ($conflictingId === null) {
                        $stats['skipped']++;
                        $this->line(sprintf(
                            '[SKIPPED] %s - sem conflito de identidade no banco %s.',
                            $tenantLabel,
                            $tenantDatabase
                        ));

                        continue;
                    }

                    if ($dryRun) {
                        $stats['skipped']++;
                        $this->warn(sprintf(
                            '[DRY-RUN] %s - conflito detectado no banco %s: slug com id "%s" diferente do id canônico "%s".',
                            $tenantLabel,
                            $tenantDatabase,
                            $conflictingId,
                            $identity['canonical_id']
                        ));

                        continue;
                    }

                    $this->tenantDatabaseManager->syncTenantRecordToTenantDatabase($tenant, $tenantDatabase, true);
                    $stats['fixed']++;
                    $this->info(sprintf(
                        '[FIXED] %s - identidade alinhada no banco %s (id canônico %s).',
                        $tenantLabel,
                        $tenantDatabase,
                        $identity['canonical_id']
                    ));
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $this->error(sprintf(
                        '[FAILED] %s - banco %s: %s',
                        $tenantLabel,
                        $tenantDatabase,
                        $e->getMessage()
                    ));
                }
            }
        } finally {
            $this->tenantDatabaseManager->setupConnection($restoreDatabase);
        }

        $this->line('');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['checked', (string) $stats['checked']],
                ['fixed', (string) $stats['fixed']],
                ['skipped', (string) $stats['skipped']],
                ['failed', (string) $stats['failed']],
            ]
        );

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
