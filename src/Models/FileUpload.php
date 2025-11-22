<?php

namespace Callcocam\LaravelRaptor\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class FileUpload extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'model_type',
        'model_id',
        'field_name',
        'status',
        'original_name',
        'mime_type',
        'size',
        'temp_path',
        'final_path',
        'thumbnails',
        'metadata',
        'progress',
        'error',
        'user_id',
    ];

    protected $casts = [
        'thumbnails' => 'array',
        'metadata' => 'array',
        'progress' => 'integer',
        'size' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'progress' => 0,
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_UPLOADING = 'uploading';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Thumbnail sizes
     */
    const THUMBNAIL_SMALL = 'small';   // 300px
    const THUMBNAIL_MEDIUM = 'medium'; // 600px
    const THUMBNAIL_LARGE = 'large';   // 1200px

    /**
     * Relacionamento polimórfico com o modelo pai
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuário que fez o upload
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUploading($query)
    {
        return $query->where('status', self::STATUS_UPLOADING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Verifica se o upload foi completado com sucesso
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Verifica se o upload falhou
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Verifica se o upload está em processamento
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, [self::STATUS_UPLOADING, self::STATUS_PROCESSING]);
    }

    /**
     * Marca o upload como completado
     */
    public function markAsCompleted(string $finalPath, ?array $thumbnails = null): self
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'final_path' => $finalPath,
            'thumbnails' => $thumbnails,
            'progress' => 100,
            'error' => null,
        ]);

        return $this;
    }

    /**
     * Marca o upload como falho
     */
    public function markAsFailed(string $error): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => $error,
        ]);

        return $this;
    }

    /**
     * Atualiza o progresso do upload
     */
    public function updateProgress(int $progress, ?string $status = null): self
    {
        $data = ['progress' => min(100, max(0, $progress))];

        if ($status) {
            $data['status'] = $status;
        }

        $this->update($data);

        return $this;
    }

    /**
     * Obtém a URL do arquivo final
     */
    public function getFileUrl(?string $disk = null): ?string
    {
        if (!$this->final_path) {
            return null;
        }

        $disk = $disk ?? config('raptor.filesystem_disk', 'public');

        return Storage::disk($disk)->url($this->final_path);
    }

    /**
     * Obtém a URL de um thumbnail específico
     */
    public function getThumbnailUrl(string $size = self::THUMBNAIL_SMALL, ?string $disk = null): ?string
    {
        if (!$this->thumbnails || !isset($this->thumbnails[$size])) {
            return null;
        }

        $disk = $disk ?? config('raptor.filesystem_disk', 'public');

        return Storage::disk($disk)->url($this->thumbnails[$size]);
    }

    /**
     * Obtém todas as URLs de thumbnails
     */
    public function getThumbnailUrls(?string $disk = null): array
    {
        if (!$this->thumbnails) {
            return [];
        }

        $disk = $disk ?? config('raptor.filesystem_disk', 'public');
        $urls = [];

        foreach ($this->thumbnails as $size => $path) {
            $urls[$size] = Storage::disk($disk)->url($path);
        }

        return $urls;
    }

    /**
     * Deleta o arquivo e seus thumbnails do storage
     */
    public function deleteFiles(?string $disk = null): bool
    {
        $disk = $disk ?? config('raptor.filesystem_disk', 'public');
        $deleted = true;

        // Deleta arquivo temporário
        if ($this->temp_path && Storage::disk($disk)->exists($this->temp_path)) {
            $deleted = Storage::disk($disk)->delete($this->temp_path) && $deleted;
        }

        // Deleta arquivo final
        if ($this->final_path && Storage::disk($disk)->exists($this->final_path)) {
            $deleted = Storage::disk($disk)->delete($this->final_path) && $deleted;
        }

        // Deleta thumbnails
        if ($this->thumbnails) {
            foreach ($this->thumbnails as $path) {
                if (Storage::disk($disk)->exists($path)) {
                    $deleted = Storage::disk($disk)->delete($path) && $deleted;
                }
            }
        }

        return $deleted;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Ao deletar o registro, deleta os arquivos também
        static::deleting(function ($fileUpload) {
            $fileUpload->deleteFiles();
        });
    }
}
