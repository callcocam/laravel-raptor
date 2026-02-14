<?php

namespace Callcocam\LaravelRaptor\Http\Controllers;

use Callcocam\LaravelRaptor\Jobs\ProcessFileUpload;
use Callcocam\LaravelRaptor\Models\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChunkedUploadController extends Controller
{
    /**
     * Recebe um chunk do arquivo
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'upload_id' => 'required|string',
            'original_name' => 'required_if:chunk_index,0|string',
            'field_name' => 'required_if:chunk_index,0|string',
            'model_type' => 'nullable|string',
            'model_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $chunkIndex = $request->input('chunk_index');
            $totalChunks = $request->input('total_chunks');
            $uploadId = $request->input('upload_id');
            $file = $request->file('file');

            $disk = config('raptor.filesystem_disk', 'public');
            $tempDir = "temp/uploads/{$uploadId}";

            // Salva o chunk
            $chunkPath = "{$tempDir}/chunk_{$chunkIndex}";
            Storage::disk($disk)->put($chunkPath, file_get_contents($file->getRealPath()));

            // Se é o primeiro chunk, cria o registro FileUpload
            if ($chunkIndex === 0) {
                $fileUpload = FileUpload::create([
                    'status' => FileUpload::STATUS_UPLOADING,
                    'original_name' => $request->input('original_name'),
                    'mime_type' => $file->getMimeType(),
                    'field_name' => $request->input('field_name'),
                    'model_type' => $request->input('model_type'),
                    'model_id' => $request->input('model_id'),
                    'user_id' => auth()->id(),
                    'progress' => 0,
                ]);

                // Salva o ID do FileUpload no cache para recuperar depois
                cache()->put("upload:{$uploadId}", $fileUpload->id, now()->addHours(24));
            }

            // Calcula o progresso
            $progress = (int) ((($chunkIndex + 1) / $totalChunks) * 100);

            return response()->json([
                'success' => true,
                'chunk_index' => $chunkIndex,
                'progress' => $progress,
                'message' => "Chunk {$chunkIndex} of {$totalChunks} uploaded successfully",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload chunk: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finaliza o upload combinando os chunks e disparando o Job
     */
    public function completeUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string',
            'total_chunks' => 'required|integer|min:1',
            'original_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $uploadId = $request->input('upload_id');
            $totalChunks = $request->input('total_chunks');
            $originalName = $request->input('original_name');

            $disk = config('raptor.filesystem_disk', 'public');
            $tempDir = "temp/uploads/{$uploadId}";

            // Verifica se todos os chunks foram enviados
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = "{$tempDir}/chunk_{$i}";
                if (! Storage::disk($disk)->exists($chunkPath)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Missing chunk {$i}",
                    ], 400);
                }
            }

            // Combina todos os chunks em um arquivo único
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $tempFilename = Str::ulid().'.'.$extension;
            $tempPath = "temp/{$tempFilename}";

            $combinedContent = '';
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = "{$tempDir}/chunk_{$i}";
                $combinedContent .= Storage::disk($disk)->get($chunkPath);
            }

            Storage::disk($disk)->put($tempPath, $combinedContent);

            // Deleta os chunks
            Storage::disk($disk)->deleteDirectory($tempDir);

            // Recupera o FileUpload do cache
            $fileUploadId = cache()->get("upload:{$uploadId}");
            if (! $fileUploadId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload record not found',
                ], 404);
            }

            $fileUpload = FileUpload::find($fileUploadId);
            if (! $fileUpload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload record not found',
                ], 404);
            }

            // Atualiza o FileUpload com o caminho temporário e tamanho
            $fileUpload->update([
                'temp_path' => $tempPath,
                'size' => Storage::disk($disk)->size($tempPath),
                'progress' => 100,
            ]);

            // Dispara o Job para processar o arquivo
            ProcessFileUpload::dispatch($fileUpload, $disk)->onQueue('default');

            // Limpa o cache
            cache()->forget("upload:{$uploadId}");

            return response()->json([
                'success' => true,
                'file_upload_id' => $fileUpload->id,
                'message' => 'Upload completed successfully. Processing in background.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete upload: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna o status de um upload (polling fallback)
     */
    public function getStatus(string $id): JsonResponse
    {
        try {
            $fileUpload = FileUpload::findOrFail($id);

            // Verifica se o usuário tem permissão para ver este upload
            if ($fileUpload->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $data = [
                'success' => true,
                'id' => $fileUpload->id,
                'status' => $fileUpload->status,
                'progress' => $fileUpload->progress,
                'original_name' => $fileUpload->original_name,
                'field_name' => $fileUpload->field_name,
            ];

            if ($fileUpload->isCompleted()) {
                $data['file_url'] = $fileUpload->getFileUrl();
                $data['thumbnail_urls'] = $fileUpload->getThumbnailUrls();
                $data['metadata'] = $fileUpload->metadata;
            }

            if ($fileUpload->isFailed()) {
                $data['error'] = $fileUpload->error;
            }

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload not found',
            ], 404);
        }
    }

    /**
     * Cancela um upload em progresso
     */
    public function cancelUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $uploadId = $request->input('upload_id');
            $disk = config('raptor.filesystem_disk', 'public');
            $tempDir = "temp/uploads/{$uploadId}";

            // Deleta os chunks
            if (Storage::disk($disk)->exists($tempDir)) {
                Storage::disk($disk)->deleteDirectory($tempDir);
            }

            // Recupera e marca o FileUpload como failed
            $fileUploadId = cache()->get("upload:{$uploadId}");
            if ($fileUploadId) {
                $fileUpload = FileUpload::find($fileUploadId);
                if ($fileUpload) {
                    $fileUpload->markAsFailed('Upload cancelled by user');
                }
                cache()->forget("upload:{$uploadId}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Upload cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel upload: '.$e->getMessage(),
            ], 500);
        }
    }
}
