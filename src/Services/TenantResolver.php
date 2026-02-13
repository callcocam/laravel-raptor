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

/**
 * Service padrão para resolver tenant baseado no domínio
 *
 * Esta implementação é simples e focada apenas na tabela de tenants.
 * Para lógicas mais complexas (Client, Store, banco separado, etc),
 * crie sua própria classe implementando TenantResolverInterface.
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
        // Cache: se já resolveu nesta requisição, retorna
        if ($this->resolved) {
            return $this->tenant;
        }

        $this->tenant = $this->detectTenant($request);
        $this->resolved = true;
        if ($this->tenant) {
            $this->storeTenantContext($this->tenant);
        }

        return $this->tenant;
    }

    /**
     * Detecta tenant baseado no domínio
     */
    protected function detectTenant(Request $request): mixed
    {
        $host = $request->getHost();
        $domain = str($host)->replace('www.', '')->toString();

        // Verifica se é contexto landlord (não precisa tenant)
        $landlordSubdomain = config('raptor.landlord.subdomain', 'landlord');
        if (str_contains($host, "{$landlordSubdomain}.")) {
            config(['app.context' => 'landlord']);

            return null;
        }

        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $domainColumn = config('raptor.tenant.subdomain_column', 'domain');

        // Busca tenant pelo domínio
        $tenant = $tenantModel::where($domainColumn, $domain)
            ->where('status', TenantStatus::Published->value)
            ->first();

        return $tenant;
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
     * Usa ResolvedTenantConfig para aplicar banco (conexão default; resolver customizado pode usar client/store).
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
