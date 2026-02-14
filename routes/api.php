<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Callcocam\LaravelRaptor\Http\Controllers\ChunkedUploadController;
use Callcocam\LaravelRaptor\Services\TenantRouteInjector;
use Illuminate\Support\Facades\Route;

// Rotas de upload em chunks
// Middleware já aplicado no ServiceProvider: ['web', 'auth']
Route::prefix('upload')->group(function () {
    Route::post('/chunk', [ChunkedUploadController::class, 'uploadChunk'])->name('upload.chunk');
    Route::post('/complete', [ChunkedUploadController::class, 'completeUpload'])->name('upload.complete');
    Route::post('/cancel', [ChunkedUploadController::class, 'cancelUpload'])->name('upload.cancel');
    Route::get('/status/{id}', [ChunkedUploadController::class, 'getStatus'])->name('upload.status');
});

$domain = parse_url(config('app.url'), PHP_URL_HOST);
if (! function_exists('getDirectoriesPath')) {
    function getDirectoriesPath(string $context): array
    {

        $defaultDirectories = [
            'landlord' => [
                'Callcocam\\LaravelRaptor\\Http\\Controllers\\Landlord' => __DIR__.'/../Http/Controllers/Landlord',
            ],
            'tenant' => [
                'App\\Http\\Controllers\\Tenant' => app_path('Http/Controllers/Tenant'),
                'Callcocam\\LaravelRaptor\\Http\\Controllers\\Tenant' => __DIR__.'/../Http/Controllers/Tenant',
            ],
        ];

        return data_get($defaultDirectories, $context, []);
    }
}

// $context = request()->getContext();
// Route::middleware(['web', 'auth', $context])
//     ->name($context . '.')
//     ->group(function () use ($context) {
//         // Suas rotas de tenant aqui
//         $injector = new TenantRouteInjector(getDirectoriesPath($context));
//         $injector->registerRoutes();
//     });
