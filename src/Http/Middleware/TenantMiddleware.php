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

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extrai o subdomínio da URL
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        if (!$subdomain) {
            abort(404, 'Tenant não encontrado.');
        }

        // Busca o tenant pelo subdomínio
        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $subdomainColumn = config('raptor.tenant.subdomain_column', 'subdomain');

        $tenant = $tenantModel::where($subdomainColumn, $subdomain)->first();

        if (!$tenant) {
            abort(404, 'Tenant não encontrado.');
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

    /**
     * Extrai o subdomínio do host
     */
    protected function extractSubdomain(string $host): ?string
    {
        $mainDomain = config('raptor.main_domain', 'localhost');
        
        // Remove o domínio principal para obter o subdomínio
        $subdomain = str_replace('.' . $mainDomain, '', $host);
        
        // Se for igual ao host original, não há subdomínio
        if ($subdomain === $host || $subdomain === 'www') {
            return null;
        }

        return $subdomain;
    }
}
