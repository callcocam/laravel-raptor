<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     * Apenas valida se usuário autenticado pertence ao tenant
     * (tenant já foi configurado pelo LandlordServiceProvider.boot())
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('tenant');

        // Se não encontrou tenant (contexto inválido), aborta
        if (! $tenant) {
            abort(404, 'Tenant não encontrado.');
        }

        // Verifica se usuário autenticado pertence ao tenant
        if ($request->user() && $request->user()->tenant_id !== $tenant->id) {
            auth()->logout();
            abort(403, 'Acesso negado. Você não tem permissão para acessar este tenant.');
        }

        // Define o contexto como tenant
        app()->instance('tenant.context', true);
        config(['app.context' => 'tenant']);

        return $next($request);
    }
}
