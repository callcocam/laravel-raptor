<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Callcocam\LaravelRaptor\Http\Tenant\ImageController;
use Callcocam\LaravelRaptor\Http\Tenant\PermissionController;
use Callcocam\LaravelRaptor\Http\Tenant\RoleController;
use Callcocam\LaravelRaptor\Http\Tenant\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Rotas para tenants (clientes)
| Acesso: {tenant}.example.com ou domínio customizado
|
| Configuração de prefixo:
| - RAPTOR_TENANT_ENABLE_PREFIX=false (padrão) -> /users, /roles
| - RAPTOR_TENANT_ENABLE_PREFIX=true + RAPTOR_TENANT_PREFIX=admin -> /admin/users
|
*/

/*
|--------------------------------------------------------------------------
| Rotas Públicas do Tenant
|--------------------------------------------------------------------------
| Rotas acessíveis sem autenticação
*/
Route::get('/', function () {
    return inertia('tenant/welcome');
})->name('tenant.home');

/*
|--------------------------------------------------------------------------
| Rotas Autenticadas do Tenant
|--------------------------------------------------------------------------
| Rotas que requerem login
*/
Route::middleware('auth')->group(function () {

    // Função helper para aplicar prefixo condicionalmente
    $applyPrefix = function (callable $callback) {
        $enablePrefix = config('raptor.tenant.enable_prefix', false);
        $prefix = config('raptor.tenant.prefix');

        // Aplica prefixo apenas se habilitado E se houver um prefixo configurado
        if ($enablePrefix && ! empty($prefix)) {
            return Route::prefix($prefix)->name('tenant.')->group($callback);
        }

        // Sem prefixo - rotas diretas
        return Route::name('tenant.')->group($callback);
    };

    // Aplica as rotas com ou sem prefixo baseado na configuração
    $applyPrefix(function () {

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
