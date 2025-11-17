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
        // Extrai o subdomínio da requisição
        $host = $request->getHost();
        $subdomain = $this->getSubdomain($host);

        // Verifica se o subdomínio é "landlord"
        if ($subdomain !== 'landlord') {
            abort(404, 'Página não encontrada.');
        }

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

    /**
     * Extrai o subdomínio do host
     */
    protected function getSubdomain(string $host): ?string
    {
        // Remove a porta se existir
        $host = explode(':', $host)[0];

        // Pega o domínio base da configuração
        $baseDomain = parse_url(config('app.url'), PHP_URL_HOST);

        // Se o host for igual ao domínio base, não há subdomínio
        if ($host === $baseDomain) {
            return null;
        }

        // Remove o domínio base do host para pegar apenas o subdomínio
        if (str_ends_with($host, ".{$baseDomain}")) {
            $subdomain = substr($host, 0, -(strlen($baseDomain) + 1));

            return $subdomain;
        }

        return null;
    }
}
