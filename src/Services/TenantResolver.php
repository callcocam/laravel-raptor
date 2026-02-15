<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Contracts\TenantResolverInterface;
use Callcocam\LaravelRaptor\Enums\TenantStatus;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;
use Callcocam\LaravelRaptor\Support\ResolvedTenantConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Service padrão para resolver tenant baseado no domínio.
 *
 * Fluxo: 1) Na central (landlord), busca o tenant principal por tenants.domain.
 * 2) Aplica a config do tenant (landlord passa para o banco do tenant se preenchido).
 * 3) Na conexão atual (banco do tenant), busca em tenant_domains o complemento (domainable: Client A, Client B, etc.).
 * 4) storeTenantContext(tenant, domainData) aplica banco Store/Client se for o caso.
 *
 * @example Configurar resolver customizado em config/raptor.php:
 * ```php
 * 'services' => [
 *     'tenant_resolver' => \App\Services\MyTenantResolver::class,
 * ]
 * ```
 */
class TenantResolver implements TenantResolverInterface
{
    protected bool $resolved = false;

    protected mixed $tenant = null;

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request): mixed
    {
        if ($this->resolved) {
            return $this->tenant;
        }

        $tenant = $this->detectTenant($request);
        $this->resolved = true;

        $this->tenant = $tenant;
        if ($tenant === null) {
            $domain = str($request->getHost())->replace('www.', '')->toString();
            $tenantId = $this->findTenantIdByDomainInCentral($domain);
            if ($tenantId !== null) {
                $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
                $this->tenant = $tenantModel::on($this->landlordConnection())->find($tenantId);
            }
            if ($this->tenant === null) {
                return null;
            }
        }

        $tenant = $this->tenant;

        // Troca landlord para o banco do tenant (se preenchido) para poder ler tenant_domains
        $configOnly = ResolvedTenantConfig::from($tenant, null);
        app(TenantDatabaseManager::class)->applyConfig($configOnly);

        // tenant_domains no banco do tenant: complemento (domainable)
        $domain = str($request->getHost())->replace('www.', '')->toString();
        $domainData = $this->findByTenantDomains($domain);

        $this->storeTenantContext($tenant, $domainData);

        return $this->tenant;
    }

    /**
     * Na central (landlord), busca tenant_id em tenant_domains pelo domínio.
     */
    protected function findTenantIdByDomainInCentral(string $domain): ?string
    {
        $conn = $this->landlordConnection();
        $tenantsTable = config('raptor.tables.tenants', 'tenants');
        $tenantDomainsTable = config('raptor.tables.tenant_domains', 'tenant_domains');

        try {
            $row = DB::connection($conn)
                ->table($tenantDomainsTable)
                ->join($tenantsTable, "{$tenantsTable}.id", '=', "{$tenantDomainsTable}.tenant_id")
                ->where("{$tenantDomainsTable}.domain", $domain)
                ->where("{$tenantsTable}.status", TenantStatus::Published->value)
                ->whereNull("{$tenantsTable}.deleted_at")
                ->select("{$tenantsTable}.id as tenant_id")
                ->first();

            return $row?->tenant_id;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Passo 1: tenant na central por tenants.domain (principal).
     */
    protected function detectTenant(Request $request): mixed
    {
        $host = $request->getHost();
        $domain = str($host)->replace('www.', '')->toString();

        $landlordSubdomain = config('raptor.landlord.subdomain', 'landlord');
        if (str_contains($host, "{$landlordSubdomain}.")) {
            config(['app.context' => 'landlord']);

            return null;
        }

        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $domainColumn = config('raptor.tenant.subdomain_column', 'domain');

        return $tenantModel::on($this->landlordConnection())
            ->where($domainColumn, $domain)
            ->where('status', TenantStatus::Published->value)
            ->first();
    }

    /**
     * Busca em tenant_domains na conexão atual (já apontando para o banco do tenant).
     * Retorna domainable_type e domainable_id para complemento (Tenant A + Client A, Client B, etc.).
     */
    protected function findByTenantDomains(string $domain): ?object
    {
        $conn = $this->landlordConnection();
        $tenantDomainsTable = config('raptor.tables.tenant_domains', 'tenant_domains');

        try {
            return DB::connection($conn)
                ->table($tenantDomainsTable)
                ->where('domain', $domain)
                ->select('domainable_type', 'domainable_id', 'is_primary')
                ->first();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Objeto domainData no formato esperado por ResolvedTenantConfig (domainable_type, domainable_id)
     */
    protected function prepareDomainData(?object $domainData): ?object
    {
        if (! $domainData || empty($domainData->domainable_type) || empty($domainData->domainable_id)) {
            return null;
        }

        return (object) [
            'domainable_type' => $domainData->domainable_type,
            'domainable_id' => (string) $domainData->domainable_id,
        ];
    }

    protected function landlordConnection(): string
    {
        return config('raptor.database.landlord_connection_name', 'landlord');
    }

    /**
     * {@inheritdoc}
     */
    public function storeTenantContext(mixed $tenant, ?object $domainData = null): void
    {
        $config = ResolvedTenantConfig::from($tenant, $domainData);

        app()->instance('tenant.context', true);
        app()->instance('current.tenant', $config->tenant);
        app()->instance('tenant', $config->tenant);
        app()->instance(ResolvedTenantConfig::class, $config);

        config($config->toAppConfig());
        Landlord::addTenant($config->tenant);

        app(TenantDatabaseManager::class)->applyConfig($config);
    }

    /**
     * {@inheritdoc}
     * Aplica ResolvedTenantConfig (landlord passa a apontar para o banco do tenant/store/client quando preenchido).
     */
    public function configureTenantDatabase(mixed $tenant, ?object $domainData = null): void
    {
        if ($tenant === null) {
            return;
        }

        $config = ResolvedTenantConfig::from($tenant, $domainData);
        app(TenantDatabaseManager::class)->applyConfig($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getTenant(): mixed
    {
        return $this->tenant;
    }

    /**
     * {@inheritdoc}
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }
}
