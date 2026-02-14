<?php

namespace Callcocam\LaravelRaptor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;

class ExportCompleted implements ShouldBroadcastNow
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
        public string $filePath,
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

    /** Resolve URL de download da exportação sem depender de rota nomeada (funciona no queue worker). */
    public static function resolveDownloadExportUrl(string $filename): string
    {
        foreach (['tenant.download.export', 'landlord.download.export', 'download.export'] as $name) {
            if (Route::has($name)) {
                return route($name, ['filename' => $filename]);
            }
        }

        return url('download-export/'.$filename);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->userId),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $filename = basename($this->filePath);

        return [
            'type' => 'export',
            'model' => $this->modelName,
            'total' => $this->totalRows,
            'filePath' => $this->filePath,
            'fileName' => $this->fileName,
            'downloadUrl' => self::resolveDownloadExportUrl($filename),
            'message' => sprintf(
                'Exportação concluída: %d registros exportados',
                $this->totalRows
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
        return 'export.completed';
    }
}
