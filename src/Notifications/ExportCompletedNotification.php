<?php

namespace Callcocam\LaravelRaptor\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExportCompletedNotification extends Notification
{
    use Queueable;

    protected string $fileName;

    protected string $downloadUrl;

    protected string $resourceName;

    protected bool $wasQueued;

    protected ?string $tenantId;

    protected ?string $tenantName;

    protected ?string $clientId;

    protected ?string $clientName;

    public function __construct(
        string $fileName,
        string $downloadUrl,
        string $resourceName = 'registros',
        bool $wasQueued = false,
        ?string $tenantId = null,
        ?string $tenantName = null,
        ?string $clientId = null,
        ?string $clientName = null
    ) {
        $this->fileName = $fileName;
        $this->downloadUrl = $downloadUrl;
        $this->resourceName = $resourceName;
        $this->wasQueued = $wasQueued;

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
     * Canais de notificação que serão usados.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Dados que serão armazenados no banco de dados.
     */
    public function toDatabase($notifiable): array
    {
        $title = $this->wasQueued
            ? 'Exportação Concluída'
            : 'Arquivo Pronto';

        $message = $this->wasQueued
            ? "Sua exportação de {$this->resourceName} foi processada e está pronta para download."
            : "Sua exportação de {$this->resourceName} está pronta para download.";

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'success',
            'download' => $this->downloadUrl,
            'fileName' => $this->fileName,
            'icon' => '📥',
            // Contexto do tenant/client
            'tenant_id' => $this->tenantId,
            'tenant_name' => $this->tenantName,
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
        ];
    }

    /**
     * Representação em array da notificação.
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
