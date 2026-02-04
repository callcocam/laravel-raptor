<?php

namespace Callcocam\LaravelRaptor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int|string $userId,
        public string $modelName,
        public int $totalRows,
        public string $filePath,
        public ?string $fileName = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->userId),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'export',
            'model' => $this->modelName,
            'total' => $this->totalRows,
            'filePath' => $this->filePath,
            'fileName' => $this->fileName,
            'downloadUrl' => route('download.export', ['path' => basename($this->filePath)]),
            'message' => sprintf(
                'Exportação concluída: %d registros exportados',
                $this->totalRows
            ),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'export.completed';
    }
}
