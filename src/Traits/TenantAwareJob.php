<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Traits;

use Callcocam\LaravelRaptor\Services\TenantDatabaseManager;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;
use Callcocam\LaravelRaptor\Support\ResolvedTenantConfig;

/**
 * Trait para Jobs que precisam do contexto do tenant
 *
 * Automaticamente captura o tenant_id na criação do job
 * e restaura o contexto antes de executar.
 *
 * @example
 * ```php
 * class ProcessOrderJob implements ShouldQueue
 * {
 *     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
 *     use \Callcocam\LaravelRaptor\Traits\TenantAwareJob;
 *
 *     public function __construct(public Order $order)
 *     {
 *         // Captura automaticamente o tenant atual
 *         $this->captureTenantContext();
 *     }
 *
 *     public function handle(): void
 *     {
 *         // O tenant já foi restaurado automaticamente
 *         // Pode acessar app('tenant'), config('app.current_tenant_id'), etc.
 *     }
 * }
 * ```
 *
 * Para jobs que usam middleware (recomendado):
 * ```php
 * public function middleware(): array
 * {
 *     return $this->tenantMiddleware();
 * }
 * ```
 */
trait TenantAwareJob
{
    /**
     * ID do tenant capturado na criação do job
     */
    public ?string $tenantId = null;

    /**
     * ID do domainable capturado (Client, Store, etc)
     */
    public ?string $domainableId = null;

    /**
     * Tipo do domainable capturado
     */
    public ?string $domainableType = null;

    /**
     * Flag para verificar se o contexto foi restaurado
     */
    protected bool $tenantContextRestored = false;

    /**
     * Captura o contexto do tenant atual
     * Chame no construtor do Job
     */
    protected function captureTenantContext(): void
    {
        $this->tenantId = config('app.current_tenant_id');
        $this->domainableId = config('app.current_domainable_id');
        $this->domainableType = config('app.current_domainable_type');
    }

    /**
     * Restaura o contexto do tenant antes de executar o job (usa ResolvedTenantConfig).
     * Chamado automaticamente se usar tenantMiddleware().
     */
    protected function restoreTenantContext(): void
    {
        if ($this->tenantContextRestored) {
            return;
        }

        if (! $this->tenantId) {
            return;
        }

        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $tenant = $tenantModel::find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $domainData = ($this->domainableType && $this->domainableId)
            ? (object) ['domainable_type' => $this->domainableType, 'domainable_id' => $this->domainableId]
            : null;

        $config = ResolvedTenantConfig::from($tenant, $domainData);

        app()->instance('tenant.context', true);
        app()->instance('current.tenant', $config->tenant);
        app()->instance('tenant', $config->tenant);
        app()->instance(ResolvedTenantConfig::class, $config);

        config($config->toAppConfig());
        Landlord::addTenant($config->tenant);

        $this->restoreDomainableContext();

        if (config('raptor.database.configure_in_jobs', true)) {
            app(TenantDatabaseManager::class)->applyConfig($config);
        }

        $this->tenantContextRestored = true;
    }

    /**
     * Restaura o contexto do domainable (Client, Store, etc).
     * Usa domainableType/domainableId já definidos na config; shortcuts current.client/store
     * só são preenchidos quando a classe existir (resolver customizado).
     */
    protected function restoreDomainableContext(): void
    {
        if (! $this->domainableType || ! $this->domainableId) {
            return;
        }

        if (! class_exists($this->domainableType)) {
            return;
        }

        $domainable = $this->domainableType::find($this->domainableId);

        if (! $domainable) {
            return;
        }

        app()->instance('current.domainable', $domainable);
        app()->instance('current.domainable_type', $this->domainableType);
        app()->instance('current.domainable_id', $this->domainableId);

        if (str_ends_with($this->domainableType, 'Client')) {
            app()->instance('current.client', $domainable);
        }

        if (str_ends_with($this->domainableType, 'Store')) {
            app()->instance('current.store', $domainable);
        }
    }

    /**
     * Retorna o middleware de tenant para o job
     * Use no método middleware() do job
     *
     * @example
     * ```php
     * public function middleware(): array
     * {
     *     return $this->tenantMiddleware();
     * }
     * ```
     */
    protected function tenantMiddleware(): array
    {
        return [
            function ($job, $next) {
                $this->restoreTenantContext();

                return $next($job);
            },
        ];
    }

    /**
     * Helper para obter o tenant do job
     */
    protected function getTenantFromJob()
    {
        if (! $this->tenantId) {
            return null;
        }

        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);

        return $tenantModel::find($this->tenantId);
    }
}
