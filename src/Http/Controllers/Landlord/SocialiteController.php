<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Models\SocialProvider;
use Callcocam\LaravelRaptor\Services\SocialiteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class SocialiteController extends Controller
{
    public function __construct(private readonly SocialiteService $service) {}

    /**
     * Redireciona o usuário para o provider OAuth.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return $this->service->redirect($provider);
    }

    /**
     * Processa o callback OAuth e autentica o usuário.
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = $this->service->handleCallback($provider);
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Falha ao autenticar com '.$provider.'. Tente novamente.']);
        }

        $user = $this->findOrCreateUser($socialUser);

        Auth::login($user, true);

        return redirect()->intended(config('fortify.home', '/dashboard'));
    }

    private function validateProvider(string $provider): void
    {
        abort_if(! in_array($provider, SocialProvider::availableProviders()), 404);
    }

    private function findOrCreateUser(\Laravel\Socialite\Contracts\User $socialUser): mixed
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $userModel */
        $userModel = config('raptor.shinobi.models.user', \App\Models\User::class);
        $tenant = app()->bound('current.tenant') ? app('current.tenant') : null;

        

        return $userModel::firstOrCreate(
            ['email' => $socialUser->getEmail(), 'tenant_id' => $tenant?->id],
            [
                'name'              => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Usuário',
                'email_verified_at' => now(),
                'password'          => bcrypt(str()->random(32)),
                'tenant_id'         => $tenant?->id,
                'status'            => 'published',
            ]
        );
    }
}
