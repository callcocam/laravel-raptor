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

class LandlordMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário tem permissão para acessar o landlord
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // Verifica se o usuário tem a role de landlord/super-admin
        if (! $request->user()->hasRole('super-admin')) {
            abort(403, 'Acesso negado. Você não tem permissão para acessar esta área.');
        }

        // Define o contexto como landlord
        app()->instance('landlord.context', true);
        config(['app.context' => 'landlord']);

        return $next($request);
    }
}
