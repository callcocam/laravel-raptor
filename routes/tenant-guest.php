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

use Illuminate\Support\Facades\Route;

// Rota de login como outro usuário (para admins)
Route::get('login-as', [\Callcocam\LaravelRaptor\Http\Controllers\LoginAsController::class, 'loginAs'])
    ->name('loginAs');
