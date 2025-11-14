<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Middleware;

use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantCustomDomainMiddleware
{
    /**
     * Handle an incoming request for custom domains.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pega o domínio completo da requisição
        $host = $request->getHost();

        // Busca o tenant pelo domínio customizado
        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $customDomainColumn = config('raptor.tenant.custom_domain_column', 'custom_domain');

        $tenant = $tenantModel::where($customDomainColumn, $host)->first();

        if (! $tenant) {
            abort(404, 'Tenant não encontrado para este domínio.');
        }

        // Verifica se o tenant está ativo
        if (isset($tenant->status) && $tenant->status !== 'active') {
            abort(403, 'Este tenant está inativo.');
        }

        // Define o tenant atual usando o Landlord
        Landlord::addTenant($tenant);

        // Define o contexto como tenant
        app()->instance('tenant.context', true);
        app()->instance('current.tenant', $tenant);
        config(['app.context' => 'tenant']);
        config(['app.current_tenant_id' => $tenant->id]);

        return $next($request);
    }
}
