<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Callcocam\LaravelRaptor\Http\Controllers\ChunkedUploadController;
use Illuminate\Support\Facades\Route;

// Rotas de upload em chunks
// Middleware já aplicado no ServiceProvider: ['web', 'auth']
Route::prefix('upload')->group(function () {
    Route::post('/chunk', [ChunkedUploadController::class, 'uploadChunk'])->name('upload.chunk');
    Route::post('/complete', [ChunkedUploadController::class, 'completeUpload'])->name('upload.complete');
    Route::post('/cancel', [ChunkedUploadController::class, 'cancelUpload'])->name('upload.cancel');
    Route::get('/status/{id}', [ChunkedUploadController::class, 'getStatus'])->name('upload.status');
});
