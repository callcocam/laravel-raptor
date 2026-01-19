<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Enums\TenantStatus;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Service centralizado para resolver e configurar tenant
 * Evita duplicação de código e melhora performance com cache por request
 */
class TenantResolver
{
    protected bool $resolved = false;
    protected mixed $tenant = null;

    /**
     * Resolve e configura tenant baseado no request
     * Usa cache interno para evitar múltiplas queries na mesma requisição
     */
    public function resolve(Request $request): mixed
    {
        // Cache: se já resolveu nesta requisição, retorna
        if ($this->resolved) {
            return $this->tenant;
        }

        $this->tenant = $this->detectAndConfigureTenant($request);
        $this->resolved = true;

        return $this->tenant;
    }

    /**
     * Detecta e configura tenant baseado no domínio
     */
    protected function detectAndConfigureTenant(Request $request): mixed
    {
        $host = $request->getHost();
        $domain = str($host)->replace('www.', '')->toString();

        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);

        // Verifica se é contexto landlord (não precisa tenant)
        if (str_contains($host, 'landlord.')) {
            config(['app.context' => 'landlord']);
            return null;
        }

        // Busca domínio com tenant e domainable em query otimizada
        $domainData = DB::table('tenant_domains')
            ->join('tenants', 'tenants.id', '=', 'tenant_domains.tenant_id')
            ->where('tenant_domains.domain', $domain)
            ->where('tenants.status', TenantStatus::Published->value)
            ->whereNull('tenants.deleted_at')
            ->select(
                'tenants.*',
                'tenant_domains.domainable_type',
                'tenant_domains.domainable_id',
                'tenant_domains.is_primary'
            )
            ->first();

        // Fallback: busca por coluna 'domain' (retrocompatibilidade)
        if (!$domainData) {
            $domainColumn = config('raptor.tenant.subdomain_column', 'domain');
            $tenant = $tenantModel::where($domainColumn, $domain)->first();

            if (!$tenant) {
                return null; // Não é tenant
            }

            $domainData = (object) [
                'id' => $tenant->id,
                'domainable_type' => null,
                'domainable_id' => null,
                'is_primary' => true,
            ];
        }

        // Converte para model instance
        $tenant = $tenantModel::find($domainData->id);

        if (!$tenant || $tenant->status !== TenantStatus::Published) {
            return null;
        }

        // Armazena contexto no container
        $this->storeTenantContext($tenant, $domainData);

        // Configura banco de dados do tenant
        $this->configureTenantDatabase($tenant, $domainData);

        return $tenant;
    }

    /**
     * Armazena contexto do tenant no container
     */
    protected function storeTenantContext($tenant, $domainData): void
    {
        app()->instance('tenant.context', true);
        app()->instance('current.tenant', $tenant);
        app()->instance('tenant', $tenant);
        
        config(['app.context' => 'tenant']);
        config(['app.current_tenant_id' => $tenant->id]);

        // Configura domainable (Client, Store, etc)
        if ($domainData->domainable_type && $domainData->domainable_id) {
            $dominableClass = $domainData->domainable_type;
            $domainable = $dominableClass::find($domainData->domainable_id);

            if ($domainable) {
                app()->instance('current.domainable', $domainable);
                app()->instance('current.domainable_type', $domainData->domainable_type);
                app()->instance('current.domainable_id', $domainData->domainable_id);

                config(['app.current_domainable_type' => $domainData->domainable_type]);
                config(['app.current_domainable_id' => $domainData->domainable_id]);

                // Shortcuts úteis por tipo
                if ($domainData->domainable_type === 'App\\Models\\Client') {
                    config(['app.current_client_id' => $domainData->domainable_id]);
                    app()->instance('current.client', $domainable);
                }

                if ($domainData->domainable_type === 'App\\Models\\Store') {
                    config(['app.current_store_id' => $domainData->domainable_id]);
                    app()->instance('current.store', $domainable);
                }
            }
        }

        Landlord::addTenant($tenant);
    }

    /**
     * Configura banco de dados do tenant
     */
    protected function configureTenantDatabase($tenant, $domainData): void
    {
        if (class_exists(TenantConnectionService::class)) {
            app(TenantConnectionService::class)->configureTenantDatabase($tenant, $domainData);
        }
    }

    /**
     * Retorna tenant já resolvido (ou null)
     */
    public function getTenant(): mixed
    {
        return $this->tenant;
    }

    /**
     * Verifica se já foi resolvido
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }
}
