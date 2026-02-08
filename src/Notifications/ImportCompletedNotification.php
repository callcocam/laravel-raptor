<?php

namespace Callcocam\LaravelRaptor\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ImportCompletedNotification extends Notification
{
    use Queueable;

    protected string $resourceName;
    protected bool $wasQueued;
    protected ?string $tenantId;
    protected ?string $tenantName;
    protected ?string $clientId;
    protected ?string $clientName;

    /** Caminho relativo ao disco local do Excel com registros que falharam. */
    protected ?string $failedReportPath = null;

    public function __construct(
        string $resourceName = 'registros',
        bool $wasQueued = false,
        ?string $tenantId = null,
        ?string $tenantName = null,
        ?string $clientId = null,
        ?string $clientName = null,
        ?string $failedReportPath = null
    ) {
        $this->resourceName = $resourceName;
        $this->wasQueued = $wasQueued;
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
            ? 'Importação Concluída' 
            : 'Registros Importados';
            
        $message = $this->wasQueued
            ? "Sua importação de {$this->resourceName} foi processada com sucesso."
            : "Os {$this->resourceName} foram importados com sucesso.";

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'success',
            'icon' => '📥',
            'failed_report_path' => $this->failedReportPath,
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
