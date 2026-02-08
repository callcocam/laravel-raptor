<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Illuminate\Support\Facades\Route;
use Callcocam\LaravelRaptor\Services\TenantRouteInjector;
use Illuminate\Support\Facades\Storage;

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
    $path = Storage::disk('local')->path('imports/' . $filename);
    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path, 'importacao-erros.xlsx')->deleteFileAfterSend(true);
})->name('download.import.failed');
