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
*/

// Prefixo para organização das rotas
Route::prefix('admin')->name('landlord.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Tenants
    |--------------------------------------------------------------------------
    */
    

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
