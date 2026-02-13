<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Traits;

use Callcocam\LaravelRaptor\Enums\TenantStatus;
use Callcocam\LaravelRaptor\Services\TenantDatabaseManager;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;
use Callcocam\LaravelRaptor\Support\ResolvedTenantConfig;
use Illuminate\Console\Command;

/**
 * Trait para Commands que precisam do contexto do tenant
 *
 * Adiciona opção --tenant para especificar o tenant
 * e métodos para restaurar o contexto.
 *
 * @example
 * ```php
 * class ProcessOrdersCommand extends Command
 * {
 *     use \Callcocam\LaravelRaptor\Traits\TenantAwareCommand;
 *
 *     protected $signature = 'orders:process {--tenant= : ID ou domínio do tenant}';
 *
 *     public function handle(): int
 *     {
 *         // Configura o tenant pelo argumento --tenant
 *         if (!$this->setupTenantFromOption()) {
 *             return Command::FAILURE;
 *         }
 *
 *         // Agora pode usar app('tenant'), config('app.current_tenant_id'), etc.
 *         $this->info("Processando para tenant: " . app('tenant')->name);
 *
 *         return Command::SUCCESS;
 *     }
 * }
 * ```
 *
 * Para iterar sobre todos os tenants:
 * ```php
 * public function handle(): int
 * {
 *     $this->forEachTenant(function ($tenant) {
 *         $this->info("Processando: {$tenant->name}");
 *         // Seu código aqui - contexto do tenant já configurado
 *     });
 *
 *     return Command::SUCCESS;
 * }
 * ```
 */
trait TenantAwareCommand
{
    /**
     * Configura o contexto do tenant a partir da opção --tenant
     *
     * @return bool True se conseguiu configurar, false se falhou
     */
    protected function setupTenantFromOption(): bool
    {
        $tenantIdentifier = $this->option('tenant');

        if (! $tenantIdentifier) {
            $this->error('A opção --tenant é obrigatória');

            return false;
        }

        $tenant = $this->findTenant($tenantIdentifier);

        if (! $tenant) {
            $this->error("Tenant não encontrado: {$tenantIdentifier}");

            return false;
        }

        $this->setupTenantContext($tenant);

        return true;
    }

    /**
     * Configura o contexto do tenant opcional (não falha se não informado)
     *
     * @return bool True se configurou um tenant, false se não tinha tenant para configurar
     */
    protected function setupTenantFromOptionIfProvided(): bool
    {
        $tenantIdentifier = $this->option('tenant');

        if (! $tenantIdentifier) {
            return false;
        }

        $tenant = $this->findTenant($tenantIdentifier);

        if (! $tenant) {
            $this->warn("Tenant não encontrado: {$tenantIdentifier}");

            return false;
        }

        $this->setupTenantContext($tenant);

        return true;
    }

    /**
     * Executa uma callback para cada tenant ativo
     *
     * @param  callable  $callback  Função que recebe o tenant
     * @param  bool  $showProgress  Exibe barra de progresso
     */
    protected function forEachTenant(callable $callback, bool $showProgress = true): void
    {
        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);

        $tenants = $tenantModel::where('status', TenantStatus::Published->value)->get();

        if ($tenants->isEmpty()) {
            $this->warn('Nenhum tenant ativo encontrado.');

            return;
        }

        $progress = $showProgress ? $this->output->createProgressBar($tenants->count()) : null;

        foreach ($tenants as $tenant) {
            // Configura contexto do tenant atual
            $this->setupTenantContext($tenant);

            try {
                $callback($tenant);
            } catch (\Exception $e) {
                $this->error("Erro no tenant {$tenant->name}: {$e->getMessage()}");
            } finally {
                // Limpa contexto para o próximo
                $this->clearTenantContext();
            }

            $progress?->advance();
        }

        $progress?->finish();
        $this->newLine();
    }

    /**
     * Busca tenant por ID ou domínio
     */
    protected function findTenant(string $identifier)
    {
        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $domainColumn = config('raptor.tenant.subdomain_column', 'domain');

        // Tenta buscar por ID (UUID ou incremental)
        $tenant = $tenantModel::find($identifier);

        if ($tenant) {
            return $tenant;
        }

        // Tenta buscar por domínio
        return $tenantModel::where($domainColumn, $identifier)->first();
    }

    /**
     * Configura o contexto de um tenant específico (usa ResolvedTenantConfig como jobs/HTTP).
     */
    protected function setupTenantContext($tenant): void
    {
        $this->clearTenantContext();

        $config = ResolvedTenantConfig::from($tenant, null);

        app()->instance('tenant.context', true);
        app()->instance('current.tenant', $config->tenant);
        app()->instance('tenant', $config->tenant);
        app()->instance(ResolvedTenantConfig::class, $config);

        config($config->toAppConfig());
        Landlord::addTenant($config->tenant);

        if (config('raptor.database.configure_in_commands', true)) {
            app(TenantDatabaseManager::class)->applyConfig($config);
        }
    }

    /**
     * Limpa o contexto do tenant atual
     */
    protected function clearTenantContext(): void
    {
        // Remove do Landlord
        Landlord::disable();

        // Limpa config
        config([
            'app.context' => null,
            'app.current_tenant_id' => null,
            'app.current_domainable_type' => null,
            'app.current_domainable_id' => null,
            'app.current_client_id' => null,
            'app.current_store_id' => null,
        ]);

        app()->forgetInstance('tenant');
        app()->forgetInstance('current.tenant');
        app()->forgetInstance('tenant.context');
        app()->forgetInstance(ResolvedTenantConfig::class);
        app()->forgetInstance('current.domainable');
        app()->forgetInstance('current.client');
        app()->forgetInstance('current.store');
    }

    /**
     * Retorna o tenant atual configurado
     */
    protected function getCurrentTenant()
    {
        return app('tenant');
    }
}
