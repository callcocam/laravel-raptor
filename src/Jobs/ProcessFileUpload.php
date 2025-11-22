<?php

namespace Callcocam\LaravelRaptor\Jobs;

use Callcocam\LaravelRaptor\Events\FileUploadProcessed;
use Callcocam\LaravelRaptor\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de tentativas
     */
    public $tries = 3;

    /**
     * Timeout em segundos
     */
    public $timeout = 300; // 5 minutos

    protected FileUpload $fileUpload;

    protected string $disk;

    /**
     * Create a new job instance.
     */
    public function __construct(FileUpload $fileUpload, ?string $disk = null)
    {
        $this->fileUpload = $fileUpload;
        $this->disk = $disk ?? config('raptor.filesystem_disk', 'public');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Marca como processando
            $this->fileUpload->updateProgress(0, FileUpload::STATUS_PROCESSING);

            // Verifica se o arquivo temporário existe
            if (!Storage::disk($this->disk)->exists($this->fileUpload->temp_path)) {
                throw new \Exception("Temporary file not found: {$this->fileUpload->temp_path}");
            }

            // Move o arquivo para o local final
            $finalPath = $this->moveTempToFinal();
            $this->fileUpload->updateProgress(30);

            // Gera thumbnails se for imagem
            $thumbnails = null;
            if ($this->isImage()) {
                $thumbnails = $this->generateThumbnails($finalPath);
                $this->fileUpload->updateProgress(80);
            } else {
                $this->fileUpload->updateProgress(100);
            }

            // Extrai metadata
            $metadata = $this->extractMetadata($finalPath);

            // Marca como completado
            $this->fileUpload->markAsCompleted($finalPath, $thumbnails);
            $this->fileUpload->update(['metadata' => $metadata]);

            // Deleta arquivo temporário
            Storage::disk($this->disk)->delete($this->fileUpload->temp_path);

            // Dispara evento de broadcast
            broadcast(new FileUploadProcessed($this->fileUpload))->toOthers();

            Log::info("File upload processed successfully: {$this->fileUpload->id}");

        } catch (\Exception $e) {
            // Marca como falho
            $this->fileUpload->markAsFailed($e->getMessage());

            Log::error("File upload processing failed: {$this->fileUpload->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lança a exceção para o sistema de retry do Laravel
            throw $e;
        }
    }

    /**
     * Move o arquivo temporário para o local final
     */
    protected function moveTempToFinal(): string
    {
        $extension = pathinfo($this->fileUpload->original_name, PATHINFO_EXTENSION);
        $filename = Str::ulid() . '.' . $extension;

        // Define o path final baseado no campo e modelo
        $directory = 'uploads';

        if ($this->fileUpload->model_type && $this->fileUpload->field_name) {
            $modelName = class_basename($this->fileUpload->model_type);
            $directory = "uploads/{$modelName}/{$this->fileUpload->field_name}";
        }

        $finalPath = "{$directory}/{$filename}";

        // Move o arquivo
        Storage::disk($this->disk)->move(
            $this->fileUpload->temp_path,
            $finalPath
        );

        return $finalPath;
    }

    /**
     * Verifica se o arquivo é uma imagem
     */
    protected function isImage(): bool
    {
        $mimeType = $this->fileUpload->mime_type;

        return $mimeType && Str::startsWith($mimeType, 'image/');
    }

    /**
     * Gera thumbnails da imagem
     */
    protected function generateThumbnails(string $imagePath): array
    {
        $thumbnails = [];
        $sizes = [
            FileUpload::THUMBNAIL_SMALL => 300,
            FileUpload::THUMBNAIL_MEDIUM => 600,
            FileUpload::THUMBNAIL_LARGE => 1200,
        ];

        // Cria o ImageManager com driver GD
        $manager = new ImageManager(new Driver());

        // Lê a imagem original
        $originalImage = $manager->read(Storage::disk($this->disk)->get($imagePath));

        foreach ($sizes as $sizeName => $width) {
            try {
                // Redimensiona mantendo proporção
                $resized = $originalImage->scale(width: $width);

                // Define o path do thumbnail
                $thumbnailPath = $this->getThumbnailPath($imagePath, $sizeName);

                // Salva o thumbnail
                $encoded = $resized->toJpeg(quality: 85);
                Storage::disk($this->disk)->put($thumbnailPath, (string) $encoded);

                $thumbnails[$sizeName] = $thumbnailPath;

            } catch (\Exception $e) {
                Log::warning("Failed to generate {$sizeName} thumbnail for {$this->fileUpload->id}: {$e->getMessage()}");
                continue;
            }
        }

        return $thumbnails;
    }

    /**
     * Gera o path para o thumbnail
     */
    protected function getThumbnailPath(string $originalPath, string $sizeName): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        return "{$directory}/{$filename}_{$sizeName}.{$extension}";
    }

    /**
     * Extrai metadata do arquivo
     */
    protected function extractMetadata(string $filePath): array
    {
        $metadata = [];

        try {
            if ($this->isImage()) {
                $manager = new ImageManager(new Driver());
                $image = $manager->read(Storage::disk($this->disk)->get($filePath));

                $metadata['width'] = $image->width();
                $metadata['height'] = $image->height();
                $metadata['aspect_ratio'] = round($image->width() / $image->height(), 2);
            }

            // Adiciona tamanho do arquivo
            $metadata['file_size'] = Storage::disk($this->disk)->size($filePath);

        } catch (\Exception $e) {
            Log::warning("Failed to extract metadata for {$this->fileUpload->id}: {$e->getMessage()}");
        }

        return $metadata;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Marca como falho se todas as tentativas falharam
        $this->fileUpload->markAsFailed($exception->getMessage());

        Log::error("File upload job failed permanently: {$this->fileUpload->id}", [
            'error' => $exception->getMessage(),
        ]);

        // Dispara evento de falha
        broadcast(new FileUploadProcessed($this->fileUpload))->toOthers();
    }
}
