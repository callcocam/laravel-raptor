<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Traits;

use Callcocam\LaravelRaptor\Contracts\TenantResolverInterface;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;

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
     * Restaura o contexto do tenant antes de executar o job
     * Chamado automaticamente se usar tenantMiddleware()
     */
    protected function restoreTenantContext(): void
    {
        if ($this->tenantContextRestored) {
            return;
        }

        if (!$this->tenantId) {
            return;
        }

        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $tenant = $tenantModel::find($this->tenantId);

        if (!$tenant) {
            return;
        }

        // Registra no container
        app()->instance('tenant.context', true);
        app()->instance('current.tenant', $tenant);
        app()->instance('tenant', $tenant);

        // Registra na config
        config(['app.context' => 'tenant']);
        config(['app.current_tenant_id' => $tenant->id]);

        // Adiciona ao Landlord para scopes automáticos
        Landlord::addTenant($tenant);

        // Restaura domainable se existir
        $this->restoreDomainableContext();

        // Configura banco de dados se necessário
        $this->configureTenantDatabaseForJob($tenant);

        $this->tenantContextRestored = true;
    }

    /**
     * Restaura o contexto do domainable (Client, Store, etc)
     */
    protected function restoreDomainableContext(): void
    {
        if (!$this->domainableType || !$this->domainableId) {
            return;
        }

        if (!class_exists($this->domainableType)) {
            return;
        }

        $domainable = $this->domainableType::find($this->domainableId);

        if (!$domainable) {
            return;
        }

        app()->instance('current.domainable', $domainable);
        app()->instance('current.domainable_type', $this->domainableType);
        app()->instance('current.domainable_id', $this->domainableId);

        config(['app.current_domainable_type' => $this->domainableType]);
        config(['app.current_domainable_id' => $this->domainableId]);

        // Shortcuts por tipo
        if ($this->domainableType === 'App\\Models\\Client') {
            config(['app.current_client_id' => $this->domainableId]);
            app()->instance('current.client', $domainable);
        }

        if ($this->domainableType === 'App\\Models\\Store') {
            config(['app.current_store_id' => $this->domainableId]);
            app()->instance('current.store', $domainable);
        }
    }

    /**
     * Configura o banco de dados do tenant para o job.
     * Passa domainData (domainable_type/domainable_id) quando existir, para resolver
     * o database do Client/Store quando a estratégia for banco separado.
     */
    protected function configureTenantDatabaseForJob($tenant): void
    {
        if (config('raptor.database.strategy') !== 'separate') {
            return;
        }

        $domainData = null;
        if ($this->domainableType && $this->domainableId) {
            $domainData = (object) [
                'domainable_type' => $this->domainableType,
                'domainable_id' => $this->domainableId,
            ];
        }

        $connectionService = app(\Callcocam\LaravelRaptor\Services\TenantConnectionService::class);
        $connectionService->configureTenantDatabase($tenant, $domainData);
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
        if (!$this->tenantId) {
            return null;
        }

        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        return $tenantModel::find($this->tenantId);
    }
}
