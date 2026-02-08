<?php

namespace Callcocam\LaravelRaptor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

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
    /** Caminho relativo ao disco local do Excel com registros que falharam (para download). */
    public ?string $failedReportPath = null;

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
        ?string $clientName = null,
        ?string $failedReportPath = null
    ) {
        $this->failedReportPath = $failedReportPath;
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

    /** Resolve URL do relatório de falhas sem depender de rota nomeada (funciona no queue worker). */
    public static function resolveDownloadImportFailedUrl(string $filename): string
    {
        foreach (['tenant.download.import.failed', 'landlord.download.import.failed', 'download.import.failed'] as $name) {
            if (Route::has($name)) {
                return route($name, ['filename' => $filename]);
            }
        }

        return url('download-import-failed/' . $filename);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        Log::info('[ImportCompleted] Broadcasting to channel', [
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
        $payload = [
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
            'failed_report_path' => $this->failedReportPath,
            // Contexto do tenant/client
            'tenant_id' => $this->tenantId,
            'tenant_name' => $this->tenantName,
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
        ];

        if ($this->failedReportPath !== null) {
            $payload['failed_report_download'] = self::resolveDownloadImportFailedUrl(basename($this->failedReportPath));
        }

        return $payload;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'import.completed';
    }
}
