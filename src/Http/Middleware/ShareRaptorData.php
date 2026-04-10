<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Middleware;

use Callcocam\LaravelRaptor\Services\NavigationService;
use Callcocam\LaravelRaptor\Services\SocialiteService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShareRaptorData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Compartilha providers de login social disponíveis para o tenant atual (acessível em todas as páginas)
        Inertia::share([
            'socialProviders' => function () {
                try {
                    $tenant = app()->bound('current.tenant') ? app('current.tenant') : null;

                    return app(SocialiteService::class)->activeProvidersForTenant($tenant)->values();
                } catch (\Throwable) {
                    return [];
                }
            },
        ]);

        // Compartilha dados do Raptor apenas quando há usuário autenticado
        Inertia::share([
            'raptor' => function () use ($request) {
                if (! $request->user()) {
                    return [
                        'navigation' => [],
                        'context' => null,
                    ];
                }

                $context = $this->detectContext($request);
                $navigationService = app(NavigationService::class);

                return [
                    'navigation' => $navigationService->buildNavigation($request->user(), $context),
                    'context' => $context,
                    'tenant' => $request->tenant ?? null,
                ];
            },
        ]);

        return $next($request);
    }

    /**
     * Detecta o contexto (tenant ou landlord) baseado na URL
     */
    protected function detectContext(Request $request): string
    {
        $host = $request->getHost();

        if (str_contains($host, 'landlord.')) {
            return 'landlord';
        }

        return 'tenant';
    }
}
