<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Illuminate\Support\Facades\Route;

$domain = parse_url(config('app.url'), PHP_URL_HOST);

// Rotas do Landlord (Administrador Principal)
// Acessível apenas em: landlord.seudominio.com
Route::domain(sprintf("landlord.%s", $domain))
    ->middleware(['web', 'auth'])
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
    });

// Rotas dos Tenants (Inquilinos/Clientes)
// Acessível em qualquer subdomínio exceto "landlord": cliente1.seudominio.com, cliente2.seudominio.com, etc
Route::domain(sprintf("{tenant}.%s", $domain))
    ->middleware(['web'])
    ->name('tenant.')
    ->group(function () {
        // Suas rotas de tenant aqui
        Route::get('/', function () {
            return 'Tenant Dashboard - ' . config('app.current_tenant_id');
        })->name('dashboard');
    });
