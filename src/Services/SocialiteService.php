<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Models\SocialProvider;
use Illuminate\Support\Collection;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;

class SocialiteService
{
    /**
     * Configura o Socialite com as credenciais do tenant e redireciona para o provider.
     */
    public function redirect(string $provider): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $config = $this->findActiveConfig($provider);
        $this->configureSocialite($provider, $config);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Processa o callback OAuth e retorna o usuário autenticado pelo provider.
     */
    public function handleCallback(string $provider): SocialUser
    {
        $config = $this->findActiveConfig($provider);
        $this->configureSocialite($provider, $config);

        return Socialite::driver($provider)->user();
    }

    /**
     * Retorna a coleção de providers ativos para o tenant (usada pelo frontend).
     *
     * @return Collection<int, array{provider: string, label: string, url: string, icon: string}>
     */
    public function activeProvidersForTenant(?object $tenant): Collection
    {
        if (! $tenant) {
            return collect();
        }

        return SocialProvider::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->get(['id', 'provider', 'name'])
            ->map(fn (SocialProvider $p) => [
                'provider' => $p->provider,
                'label'    => $p->name,
                'url'      => url('/auth/social/'.$p->provider.'/redirect'),
                'icon'     => $p->provider,
            ]);
    }

    private function findActiveConfig(string $provider): SocialProvider
    {
        $tenant = app()->bound('current.tenant') ? app('current.tenant') : null;

        return SocialProvider::where('provider', $provider)
            ->where('status', 'published')
            ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id))
            ->firstOrFail();
    }

    private function configureSocialite(string $provider, SocialProvider $config): void
    {
        $redirect = $config->redirect_uri
            ?? url('/auth/social/'.$provider.'/callback');

        config([
            "services.{$provider}" => [
                'client_id'     => $config->client_id,
                'client_secret' => $config->client_secret,
                'redirect'      => $redirect,
            ],
        ]);
    }
}
