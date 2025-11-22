<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Illuminate\Support\Facades\Route;
use Callcocam\LaravelRaptor\Http\Controllers\Api\NavigationController;
use Callcocam\LaravelRaptor\Services\TenantRouteInjector;

$domain = parse_url(config('app.url'), PHP_URL_HOST);

function getDirectoriesPath(string $context): array
{

    $defaultDirectories = [
        'landlord' => [
            'Callcocam\\LaravelRaptor\\Http\\Controllers\\Landlord' => __DIR__ . '/../Http/Controllers/Landlord',
        ],
        'tenant' => [
            'App\\Http\\Controllers\\Tenant' => app_path('Http/Controllers/Tenant'),
            'Callcocam\\LaravelRaptor\\Http\\Controllers\\Tenant' => __DIR__ . '/../Http/Controllers/Tenant',
        ],
    ];
    return data_get($defaultDirectories, $context, []);
}

$context = request()->getContext();
Route::middleware(['web', 'auth', $context])
    ->name($context . '.')
    ->group(function () use ($context) {
        // Suas rotas de tenant aqui
        $injector = new TenantRouteInjector(getDirectoriesPath($context));
        $injector->registerRoutes();
    });
// Rotas do Landlord (Administrador Principal)
// Acessível apenas em: landlord.seudominio.com
// Route::domain(sprintf('landlord.%s', $domain))
//     ->middleware(['web', 'auth', 'landlord'])
//     ->name('landlord.')
//     ->group(function () {
//         // Suas rotas de landlord aqui

//         Route::get('/dashboard', [\Callcocam\LaravelRaptor\Http\Controllers\Landlord\DashboardController::class, 'index'])->name('dashboard');
//         Route::resource('tenants', \Callcocam\LaravelRaptor\Http\Controllers\Landlord\TenantController::class);
//         Route::resource('users', \Callcocam\LaravelRaptor\Http\Controllers\Landlord\UserController::class);
//         Route::resource('roles', \Callcocam\LaravelRaptor\Http\Controllers\Landlord\RoleController::class);
//         Route::resource('permissions', \Callcocam\LaravelRaptor\Http\Controllers\Landlord\PermissionController::class);
//     });
