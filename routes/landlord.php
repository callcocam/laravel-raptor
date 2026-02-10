<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 * Rotas exclusivas do contexto LANDLORD
 * 
 * Este arquivo é carregado apenas quando o contexto é landlord.
 * O TenantRouteInjector escaneia dinamicamente os controllers em:
 * - app/Http/Controllers/Landlord (aplicação - dinâmico)
 * - package/src/Http/Controllers/Landlord (pacote)
 */

use Illuminate\Support\Facades\Route;
use Callcocam\LaravelRaptor\Services\TenantRouteInjector;
use Illuminate\Support\Facades\Storage;

// Registra rotas dinâmicas dos controllers Landlord usando a configuração
TenantRouteInjector::forContext('landlord')->registerRoutes();

// Rota de download de exportações
Route::get('download-export/{filename}', function ($filename) {
    $path = Storage::disk(config('raptor.export.disk', 'public'))->path('exports/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->download($path)->deleteFileAfterSend(true);
})->name('download.export');

// Rota de download do relatório de linhas que falharam na importação (mesmo padrão do tenant)
Route::get('download-import-failed/{filename}', function (string $filename) {
    if (! str_starts_with($filename, 'failed-') || ! str_ends_with($filename, '.xlsx')) {
        abort(404);
    }
    $path = Storage::disk(config('raptor.export.disk', 'public'))->path('imports/' . $filename);
    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path, 'importacao-erros.xlsx')->deleteFileAfterSend(true);
})->name('download.import.failed');

// Rota de atualização de tema
Route::put('/tenant/update-theme', [\Callcocam\LaravelRaptor\Http\Controllers\TenantThemeController::class, 'update'])
    ->name('tenant.update-theme');

// Rota de execução genérica
Route::post('/execute', config('laravel-raptor.execute_controller', \Callcocam\LaravelRaptor\Http\Controllers\ExecuteController::class) )
    ->name('execute');

// Rotas de notificações
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'index'])
        ->name('index');
    Route::post('{id}/read', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'markAsRead'])
        ->name('read');
    Route::post('read-all', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'markAllAsRead'])
        ->name('read-all');
    Route::delete('{id}', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'destroy'])
        ->name('destroy');
    Route::delete('/', [\Callcocam\LaravelRaptor\Http\Controllers\NotificationController::class, 'destroyAll'])
        ->name('destroy-all');
});

// Rota de login como outro usuário
Route::middleware('guest')->get('login-as', [\Callcocam\LaravelRaptor\Http\Controllers\LoginAsController::class, 'loginAs'])
    ->name('loginAs');
