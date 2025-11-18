<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Callcocam\LaravelRaptor\Http\Landlord\ImageController;
use Callcocam\LaravelRaptor\Http\Landlord\PermissionController;
use Callcocam\LaravelRaptor\Http\Landlord\RoleController;
use Callcocam\LaravelRaptor\Http\Landlord\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landlord Routes
|--------------------------------------------------------------------------
|
| Rotas para gerenciamento da aplicação e tenants
| Acesso: landlord.example.com
|
| Configuração de prefixo:
| - RAPTOR_LANDLORD_ENABLE_PREFIX=false (padrão) -> /users, /roles
| - RAPTOR_LANDLORD_ENABLE_PREFIX=true + RAPTOR_LANDLORD_PREFIX=admin -> /admin/users
|
*/

// Função helper para aplicar prefixo condicionalmente
$applyPrefix = function (callable $callback) {
    $enablePrefix = config('raptor.landlord.enable_prefix', false);
    $prefix = config('raptor.landlord.prefix');

    // Aplica prefixo apenas se habilitado E se houver um prefixo configurado
    if ($enablePrefix && ! empty($prefix)) {
        return Route::prefix($prefix)->name('landlord.')->group($callback);
    }

    // Sem prefixo - rotas diretas
    return Route::name('landlord.')->group($callback);
};

// Aplica as rotas com ou sem prefixo baseado na configuração
$applyPrefix(function () {

    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Usuários
    |--------------------------------------------------------------------------
    */
    Route::resource('users', UserController::class);

    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Roles (Funções)
    |--------------------------------------------------------------------------
    */
    Route::resource('roles', RoleController::class);

    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Permissões
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
