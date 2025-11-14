<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Callcocam\LaravelRaptor\Http\Tenant\PermissionController;
use Callcocam\LaravelRaptor\Http\Tenant\RoleController;
use Callcocam\LaravelRaptor\Http\Tenant\UserController;
use Callcocam\LaravelRaptor\Http\Tenant\ImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Rotas para tenants (clientes)
| Acesso: {tenant}.example.com ou domínio customizado
|
*/

// Rotas públicas do tenant (antes do login)
Route::get('/', function () {
    return inertia('tenant/welcome');
})->name('tenant.home');

// Rotas autenticadas do tenant
Route::middleware('auth')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Administração do Tenant
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('tenant.')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Gerenciamento de Usuários (do próprio tenant)
        |--------------------------------------------------------------------------
        */
        Route::resource('users', UserController::class);

        /*
        |--------------------------------------------------------------------------
        | Gerenciamento de Roles (do próprio tenant)
        |--------------------------------------------------------------------------
        */
        Route::resource('roles', RoleController::class);

        /*
        |--------------------------------------------------------------------------
        | Gerenciamento de Permissões (do próprio tenant)
        |--------------------------------------------------------------------------
        */
        Route::resource('permissions', PermissionController::class);

        /*
        |--------------------------------------------------------------------------
        | Upload de Imagens
        |--------------------------------------------------------------------------
        */
        Route::prefix('images')->name('images.')->group(function () {
            Route::post('upload', [ImageController::class, 'upload'])->name('upload');
            Route::delete('{id}', [ImageController::class, 'destroy'])->name('destroy');
        });


    });
});
