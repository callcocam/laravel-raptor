<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UploadField extends Column
{
    protected array $acceptedFileTypes = [];

    protected array $acceptedMimeTypes = [];

    protected array $acceptedExtensions = [];

    protected bool $isRequired = false;

    protected ?int $maxSize = null; // em MB

    protected string $directory = 'uploads';

    protected ?string $disk = null;

    protected bool $deleteOldFiles = false;

    protected ?string $attachmentModel = null;

    protected ?Closure $beforeUpload = null;

    protected ?Closure $afterUpload = null;

    protected ?Closure $filenameGenerator = null;

    // Async upload properties
    protected bool $async = false;

    protected ?int $chunkSize = null; // em bytes (padrão: 5MB)

    protected ?string $modelType = null;

    protected ?string $modelId = null;

    protected ?string $realName = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->name($name)
            ->label($label ?? 'Upload')
            ->component('form-field-file-upload'); // Componente padrão (simples)
        $this->setUp();

        // Configuração padrão segura
        $this->valueUsing(function ($data, $model) {
            $url = $this->handleUpload($data, $model);
            return [
                $this->getName() => $url,
                $this->getRealName() ?? $this->getName() => $url,
            ];
        });
    }

    /**
     * Atualiza o componente baseado no modo (sync/async)
     */
    protected function updateComponent(): void
    {
        if ($this->async) {
            $this->component('form-field-file-upload-async');
        } else {
            $this->component('form-field-file-upload');
        }
    }

    /**
     * Processa o upload de arquivo(s) com validações de segurança
     */
    protected function handleUpload($data, $model)
    {
        $uploadedFiles = data_get($data, $this->getName());
        // Se não há arquivos, retorna os valores existentes
        if (!$uploadedFiles) {
            return data_get($data, $this->getName());
        }

        // Se é modo async, o valor é o ID do FileUpload, não um arquivo
        if ($this->async) {
            return $this->handleAsyncUpload($uploadedFiles, $model);
        }

        // Modo síncrono - processa arquivos normalmente
        return $this->handleSyncUpload($uploadedFiles, $model);
    }

    /**
     * Processa upload assíncrono (recebe FileUpload IDs)
     */
    protected function handleAsyncUpload($fileUploadIds, $model)
    {
        // Normaliza para array
        $ids = is_array($fileUploadIds) ? $fileUploadIds : [$fileUploadIds];
        $processedIds = [];

        foreach ($ids as $id) {
            if (!$id) {
                continue;
            }

            // Verifica se o FileUpload existe e está completo
            $fileUpload = \Callcocam\LaravelRaptor\Models\FileUpload::find($id);

            if (!$fileUpload || !$fileUpload->isCompleted()) {
                continue;
            }

            // Se deve associar ao modelo
            if ($model && $this->modelType && $this->modelId) {
                $fileUpload->update([
                    'model_type' => $this->modelType,
                    'model_id' => $this->modelId,
                ]);
            }

            // Callback after upload
            if ($this->afterUpload) {
                call_user_func($this->afterUpload, $fileUpload, $model, $fileUpload->final_path);
            }

            $processedIds[] = $fileUpload->final_path;
        }

        // Deleta arquivos antigos se configurado
        if ($this->deleteOldFiles && $model) {
            $this->deleteOldFilesFromModel($model);
        }

        // Retorna string se for upload único, array se múltiplo
        if (!$this->multiple && count($processedIds) === 1) {
            return $processedIds[0];
        }

        return $processedIds;
    }

    /**
     * Processa upload síncrono (recebe UploadedFile)
     */
    protected function handleSyncUpload($uploadedFiles, $model)
    {
        // Normaliza para array se for upload único
        $files = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
        $processedFiles = [];

        foreach ($files as $file) {
            // Valida o arquivo
            if (!$this->validateFile($file)) {
                continue;
            }

            // Callback before upload
            if ($this->beforeUpload) {
                $result = call_user_func($this->beforeUpload, $file, $model);
                if ($result === false) {
                    continue; // Skip este arquivo
                }
            }

            // Se deve salvar em tabela separada (attachment)
            if ($this->attachmentModel) {
                $attachment = $this->uploadAndSaveAsAttachment($file, $model);
                $processedFiles[] = $attachment?->id;
            } else {
                // Upload direto para storage
                $path = $this->storeFile($file, $model);
                if ($path) {
                    $processedFiles[] = $path;
                }
            }

            // Callback after upload
            if ($this->afterUpload) {
                call_user_func($this->afterUpload, $file, $model, end($processedFiles));
            }
        }

        // Deleta arquivos antigos se configurado
        if ($this->deleteOldFiles && $model) {
            $this->deleteOldFilesFromModel($model);
        }

        // Retorna string se for upload único, array se múltiplo
        if (!$this->multiple && count($processedFiles) === 1) {
            return $processedFiles[0];
        }

        return $processedFiles;
    }

    /**
     * Valida o arquivo com regras de segurança
     */
    protected function validateFile($file): bool
    {
        if (!$file instanceof UploadedFile) {
            return false;
        }

        if (!$file->isValid()) {
            return false;
        }

        $rules = [];

        // Validação de MIME types
        if (!empty($this->acceptedMimeTypes)) {
            $rules['mimetypes'] = 'mimetypes:' . implode(',', $this->acceptedMimeTypes);
        }

        // Validação de extensões
        if (!empty($this->acceptedExtensions)) {
            $rules['extensions'] = 'mimes:' . implode(',', $this->acceptedExtensions);
        }

        // Validação de tamanho (em KB)
        if ($this->maxSize) {
            $rules['max'] = 'max:' . ($this->maxSize * 1024);
        }

        if (empty($rules)) {
            return true;
        }

        $validator = Validator::make(
            ['file' => $file],
            ['file' => array_values($rules)]
        );

        return $validator->passes();
    }

    /**
     * Armazena o arquivo no storage
     */
    protected function storeFile(UploadedFile $file, $model): ?string
    {
        $disk = $this->disk ?? config('raptor.filesystem_disk', 'public');

        // Gera nome do arquivo (usa callback customizado se existir)
        if ($this->filenameGenerator) {
            $filename = call_user_func($this->filenameGenerator, $file, $model);
            return $file->storeAs($this->directory, $filename, $disk);
        }

        return $file->store($this->directory, $disk);
    }

    /**
     * Faz upload e salva como attachment em tabela separada
     */
    protected function uploadAndSaveAsAttachment(UploadedFile $file, $model)
    {
        if (!$this->attachmentModel || !class_exists($this->attachmentModel)) {
            return null;
        }

        // Primeiro armazena o arquivo
        $path = $this->storeFile($file, $model);

        if (!$path) {
            return null;
        }

        // Cria o registro do attachment
        $attachment = new $this->attachmentModel();
        $attachment->file_path = $path;
        $attachment->file_name = $file->getClientOriginalName();
        $attachment->mime_type = $file->getMimeType();
        $attachment->size = $file->getSize();

        // Se o modelo tem relationship, associa
        if ($model && method_exists($model, 'attachments')) {
            $model->attachments()->save($attachment);
        } else {
            $attachment->save();
        }

        return $attachment;
    }

    /**
     * Deleta arquivos antigos do modelo
     */
    protected function deleteOldFilesFromModel($model): void
    {
        $oldValue = $model->getOriginal($this->getName());

        if (!$oldValue) {
            return;
        }

        $disk = $this->disk ?? config('raptor.filesystem_disk', 'public');
        $files = is_array($oldValue) ? $oldValue : [$oldValue];

        foreach ($files as $file) {
            if ($file && Storage::disk($disk)->exists($file)) {
                Storage::disk($disk)->delete($file);
            }
        }
    }

    /**
     * Define os tipos de arquivo aceitos (para o frontend)
     */
    public function acceptedFileTypes(array $types): self
    {
        $this->acceptedFileTypes = $types;
        return $this;
    }

    /**
     * Define os MIME types permitidos (validação backend)
     * Ex: ['image/jpeg', 'image/png', 'application/pdf']
     */
    public function acceptedMimeTypes(array $mimeTypes): self
    {
        $this->acceptedMimeTypes = $mimeTypes;
        return $this;
    }

    /**
     * Define as extensões permitidas (validação backend)
     * Ex: ['jpg', 'png', 'pdf']
     */
    public function acceptedExtensions(array $extensions): self
    {
        $this->acceptedExtensions = $extensions;
        return $this;
    }

    /**
     * Define o tamanho máximo em MB
     */
    public function maxSize(int $sizeInMB): self
    {
        $this->maxSize = $sizeInMB;
        return $this;
    }

    /**
     * Define o diretório de upload
     */
    public function directory(string $directory): self
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * Define o disco de armazenamento
     */
    public function disk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Permite upload múltiplo
     * @override Sobrescreve o método da trait para aceitar apenas bool
     */
    public function multiple(bool|Closure $multiple = true): static
    {
        // Se for Closure, avalia
        if ($multiple instanceof Closure) {
            $this->multiple = (bool) $this->evaluate($multiple);
        } else {
            $this->multiple = $multiple;
        }
        return $this;
    }

    /**
     * Deleta arquivos antigos ao fazer upload de novos
     */
    public function deleteOldFiles(bool $delete = true): self
    {
        $this->deleteOldFiles = $delete;
        return $this;
    }

    /**
     * Salva os arquivos em uma tabela separada (relacionamento)
     * Ex: ->storeAsAttachment(App\Models\Attachment::class)
     */
    public function storeAsAttachment(string $modelClass): self
    {
        $this->attachmentModel = $modelClass;
        return $this;
    }

    /**
     * Callback executado antes do upload
     * Retorne false para cancelar o upload do arquivo
     */
    public function beforeUpload(Closure $callback): self
    {
        $this->beforeUpload = $callback;
        return $this;
    }

    /**
     * Callback executado após o upload
     */
    public function afterUpload(Closure $callback): self
    {
        $this->afterUpload = $callback;
        return $this;
    }

    /**
     * Gerador de nome customizado para arquivos
     * Ex: ->filenameGenerator(fn($file, $model) => $model->id . '_' . time() . '.' . $file->extension())
     */
    public function filenameGenerator(Closure $callback): self
    {
        $this->filenameGenerator = $callback;
        return $this;
    }

    /**
     * Atalho para configurar upload de imagens com validações comuns
     */
    public function image(): self
    {
        return $this
            ->acceptedMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->acceptedExtensions(['jpg', 'jpeg', 'png', 'gif', 'webp'])
            ->acceptedFileTypes(['image/*'])
            ->maxSize(5); // 5MB
    }

    /**
     * Atalho para configurar upload de documentos
     */
    public function document(): self
    {
        return $this
            ->acceptedMimeTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->acceptedExtensions(['pdf', 'doc', 'docx'])
            ->acceptedFileTypes(['.pdf,.doc,.docx'])
            ->maxSize(10); // 10MB
    }

    /**
     * Habilita modo assíncrono (upload com Jobs e WebSocket)
     * Ideal para arquivos grandes (>50MB)
     */
    public function async(bool $async = true): self
    {
        $this->async = $async;
        $this->updateComponent();
        return $this;
    }

    /**
     * Define o tamanho do chunk para upload (em MB)
     * Padrão: 5MB
     */
    public function chunkSize(int $sizeInMB): self
    {
        $this->chunkSize = $sizeInMB * 1024 * 1024; // Converte para bytes
        return $this;
    }

    /**
     * Define o tipo de modelo associado (para uso com async)
     */
    public function forModel(string $modelType, ?string $modelId = null): self
    {
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        return $this;
    }

    /**
     * Atalho para configurar upload assíncrono de imagens grandes
     * Usa chunks e processa em background
     */
    public function largeImage(): self
    {
        return $this
            ->image()
            ->async()
            ->maxSize(100) // 100MB
            ->chunkSize(5); // 5MB chunks
    }

    /**
     * Atalho para configurar upload assíncrono de vídeos
     */
    public function video(): self
    {
        return $this
            ->acceptedMimeTypes(['video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm'])
            ->acceptedExtensions(['mp4', 'mpeg', 'mov', 'webm'])
            ->acceptedFileTypes(['video/*'])
            ->async()
            ->maxSize(500) // 500MB
            ->chunkSize(10); // 10MB chunks
    }

    public function toArray($model = null): array
    {
        $data = array_merge(parent::toArray($model), [
            'acceptedFileTypes' => $this->acceptedFileTypes,
            'maxSize' => $this->maxSize,
            'multiple' => $this->multiple,
            'directory' => $this->directory,
            'async' => $this->async,
        ]);

        // Adiciona propriedades async se habilitado
        if ($this->async) {
            $data['chunkSize'] = $this->chunkSize ?? (5 * 1024 * 1024); // 5MB default
            $data['modelType'] = $this->modelType;
            $data['modelId'] = $this->modelId;
            $data['userId'] = auth()->id();
        }

        return $data;
    }

    public function getRealName(): ?string
    {
        return $this->realName;
    }

    public function realName(?string $realName): self
    {
        $this->realName = $realName;
        return $this;
    }
}
