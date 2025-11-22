<?php

namespace Callcocam\LaravelRaptor\Events;

use Callcocam\LaravelRaptor\Models\FileUpload;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileUploadProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public FileUpload $fileUpload;

    /**
     * Create a new event instance.
     */
    public function __construct(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast para o canal privado do usuário que fez o upload
        return [
            new PrivateChannel('App.Models.User.' . $this->fileUpload->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'file-upload.processed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $data = [
            'id' => $this->fileUpload->id,
            'status' => $this->fileUpload->status,
            'progress' => $this->fileUpload->progress,
            'field_name' => $this->fileUpload->field_name,
            'model_type' => $this->fileUpload->model_type,
            'model_id' => $this->fileUpload->model_id,
        ];

        // Adiciona URLs se completado
        if ($this->fileUpload->isCompleted()) {
            $data['file_url'] = $this->fileUpload->getFileUrl();
            $data['thumbnail_urls'] = $this->fileUpload->getThumbnailUrls();
            $data['final_path'] = $this->fileUpload->final_path;
            $data['metadata'] = $this->fileUpload->metadata;
        }

        // Adiciona erro se falhou
        if ($this->fileUpload->isFailed()) {
            $data['error'] = $this->fileUpload->error;
        }

        return $data;
    }
}
