<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Illuminate\Support\Facades\Route;
use Callcocam\LaravelRaptor\Services\TenantRouteInjector;

$domain = parse_url(config('app.url'), PHP_URL_HOST);

if (!function_exists('getDirectoriesPath')) {
    function getDirectoriesPath(string $context): array
    {

        $defaultDirectories = [
            'landlord' => [
                'Callcocam\\LaravelRaptor\\Http\\Controllers\\Landlord' => __DIR__ . '/../src/Http/Controllers/Landlord',
            ],
            'tenant' => [
                'App\\Http\\Controllers\\Tenant' => app_path('Http/Controllers/Tenant'),
                'Callcocam\\LaravelRaptor\\Http\\Controllers\\Tenant' => __DIR__ . '/../src/Http/Controllers/Tenant',
            ],
        ];
        return data_get($defaultDirectories, $context, []);
    }
}

$context = request()->getContext();
Route::middleware(['web', 'auth', $context])
    ->name($context . '.')
    ->group(function () use ($context) {
        // Suas rotas de tenant aqui
        $injector = new TenantRouteInjector(getDirectoriesPath($context));
        $injector->registerRoutes();

        Route::put('/tenant/update-theme', [\Callcocam\LaravelRaptor\Http\Controllers\TenantThemeController::class, 'update'])
            ->name('tenant.update-theme');

        Route::post('/execute', [config('laravel-raptor.execute_controller', \Callcocam\LaravelRaptor\Http\Controllers\ExecuteController::class), 'execute'])
            ->name('execute');
    });



$context = request()->getContext();
Route::middleware(['web', $context])
    ->group(function () use ($context) {
        Route::middleware('guest')->get('login-as', [\Callcocam\LaravelRaptor\Http\Controllers\LoginAsController::class, 'loginAs'])
            ->name($context . '.loginAs');
    });
