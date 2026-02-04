<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Illuminate\Support\Facades\Route;
use Callcocam\LaravelRaptor\Services\TenantRouteInjector;
use Illuminate\Support\Facades\Storage;

$domain = parse_url(config('app.url'), PHP_URL_HOST);

// Rota de download de exportações
Route::get('download-export/{filename}', function ($filename) {
    $path = Storage::disk('local')->path('exports/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->download($path)->deleteFileAfterSend(true);
})->name('download.export');

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

        // Rotas de notificações
        Route::get('/notifications', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::post('/notifications/{id}/read', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'markAsRead'])
            ->name('notifications.read');
        Route::post('/notifications/read-all', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
        Route::delete('/notifications/{id}', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'destroy'])
            ->name('notifications.destroy');
        Route::delete('/notifications', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'destroyAll'])
            ->name('notifications.destroy-all');
    });



$context = request()->getContext();
Route::middleware(['web', $context])
    ->group(function () use ($context) {
        Route::middleware('guest')->get('login-as', [\Callcocam\LaravelRaptor\Http\Controllers\LoginAsController::class, 'loginAs'])
            ->name($context . '.loginAs');
    });
