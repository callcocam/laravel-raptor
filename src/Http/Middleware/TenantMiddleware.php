<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Middleware;

use Callcocam\LaravelRaptor\Enums\TenantStatus;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $domain = str($host)->replace('www.', '')->toString();

        // Busca o tenant pelo domínio
        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $domainColumn = config('raptor.tenant.subdomain_column', 'domain');

        $tenant = $tenantModel::where($domainColumn, $domain)->first();

        if (! $tenant) {
            abort(404, 'Tenant não encontrado.');
        }

        // Verifica se o tenant está ativo
        if ($tenant->status !== TenantStatus::Published) {
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
