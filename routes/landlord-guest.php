<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 *
 * Rotas GUEST do contexto LANDLORD (sem autenticação)
 *
 * Este arquivo é carregado sem middleware de autenticação.
 * Use para rotas públicas do landlord.
 */

use Callcocam\LaravelRaptor\Http\Controllers\Landlord\SocialiteController;

/*
|--------------------------------------------------------------------------
| Rotas de Login Social (OAuth)
|--------------------------------------------------------------------------
|
| Rotas públicas para o fluxo OAuth via Laravel Socialite.
| O provider é validado contra a lista de drivers suportados.
|
*/
Route::prefix('auth/social')->name('social.')->group(function () {
    Route::get('{provider}/redirect', [SocialiteController::class, 'redirect'])->name('redirect');
    Route::get('{provider}/callback', [SocialiteController::class, 'callback'])->name('callback');
});
