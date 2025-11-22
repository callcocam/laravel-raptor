<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Illuminate\Support\Facades\Route;
use Callcocam\LaravelRaptor\Http\Controllers\Api\NavigationController; 

$domain = parse_url(config('app.url'), PHP_URL_HOST);

// Rotas do Landlord (Administrador Principal)
// Acessível apenas em: landlord.seudominio.com
Route::domain(sprintf('landlord.%s', $domain))
    ->middleware(['web', 'auth', 'landlord'])
    ->name('landlord.')
    ->group(function () {
        // Suas rotas de landlord aqui
        Route::get('/', function () {
            return 'Landlord Dashboard';
        })->name('dashboard');

        Route::resource('tenants', \Callcocam\LaravelRaptor\Http\Controllers\Landlord\TenantController::class);
        Route::resource('users', \Callcocam\LaravelRaptor\Http\Controllers\Landlord\UserController::class);
        Route::resource('roles', \Callcocam\LaravelRaptor\Http\Controllers\Landlord\RoleController::class);
        Route::resource('permissions', \Callcocam\LaravelRaptor\Http\Controllers\Landlord\PermissionController::class);

        // API de Navegação
        Route::get('/api/navigation', [NavigationController::class, 'index'])->name('api.navigation.index');
        Route::post('/api/navigation/cache/clear', [NavigationController::class, 'clearCache'])->name('api.navigation.cache.clear');
    });
