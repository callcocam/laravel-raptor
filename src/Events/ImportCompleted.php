<?php

namespace Callcocam\LaravelRaptor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?string $tenantId;
    public ?string $tenantName;
    public ?string $clientId;
    public ?string $clientName;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int|string $userId,
        public string $modelName,
        public int $totalRows,
        public int $successfulRows,
        public int $failedRows = 0,
        public ?string $fileName = null,
        ?string $tenantId = null,
        ?string $tenantName = null,
        ?string $clientId = null,
        ?string $clientName = null
    ) {
        // Captura contexto atual se não foi passado
        $this->tenantId = $tenantId ?? config('app.current_tenant_id');
        $this->tenantName = $tenantName ?? $this->resolveTenantName();
        $this->clientId = $clientId ?? config('app.current_client_id');
        $this->clientName = $clientName ?? $this->resolveClientName();
    }
    
    protected function resolveTenantName(): ?string
    {
        if (app()->bound('tenant') && $tenant = app('tenant')) {
            return $tenant->name ?? null;
        }
        return null;
    }
    
    protected function resolveClientName(): ?string
    {
        if (app()->bound('current.client') && $client = app('current.client')) {
            return $client->name ?? null;
        }
        return null;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        \Log::info('[ImportCompleted] Broadcasting to channel', [
            'userId' => $this->userId,
            'channel' => 'users.' . $this->userId,
            'event' => 'import.completed',
        ]);
        
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
            'type' => 'import',
            'model' => $this->modelName,
            'total' => $this->totalRows,
            'successful' => $this->successfulRows,
            'failed' => $this->failedRows,
            'fileName' => $this->fileName,
            'message' => sprintf(
                'Importação concluída: %d registros processados (%d com sucesso, %d com erro)',
                $this->totalRows,
                $this->successfulRows,
                $this->failedRows
            ),
            'timestamp' => now()->toISOString(),
            // Contexto do tenant/client
            'tenant_id' => $this->tenantId,
            'tenant_name' => $this->tenantName,
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'import.completed';
    }
}
