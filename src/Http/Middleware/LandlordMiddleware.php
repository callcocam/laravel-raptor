<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Middleware;

use Callcocam\LaravelRaptor\Services\DomainDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LandlordMiddleware
{
    public function __construct(
        protected DomainDetectionService $domainDetection
    ) {}
    
    /**
     * Handle an incoming request.
     * 
     * Valida se a request é do contexto landlord.
     * Se não for, retorna 404 para evitar acesso às rotas de landlord.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se é realmente o contexto landlord
        if (!$this->domainDetection->isLandlord($request)) {
            abort(404);
        }
        
        // Define o contexto como landlord
        app()->instance('landlord.context', true);
        config(['app.context' => 'landlord']);

        return $next($request);
    }
}
