<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 *
 * Rotas GUEST do contexto TENANT (sem autenticação)
 *
 * Este arquivo é carregado sem middleware de autenticação.
 * Use para rotas públicas como login-as, páginas públicas, etc.
 */

use Callcocam\LaravelRaptor\Http\Controllers\Landlord\SocialiteController;
use Illuminate\Support\Facades\Route;

// Rota de login como outro usuário (para admins)
Route::get('login-as', [\Callcocam\LaravelRaptor\Http\Controllers\LoginAsController::class, 'loginAs'])
    ->name('loginAs');

/*
|--------------------------------------------------------------------------
| Rotas de Login Social (OAuth)
|--------------------------------------------------------------------------
|
| Rotas públicas para o fluxo OAuth via Laravel Socialite.
| Necessário no contexto tenant pois o login ocorre no domínio do tenant.
|
*/
Route::prefix('auth/social')->name('social.')->group(function () {
    Route::get('{provider}/redirect', [SocialiteController::class, 'redirect'])->name('redirect');
    Route::get('{provider}/callback', [SocialiteController::class, 'callback'])->name('callback');
});
