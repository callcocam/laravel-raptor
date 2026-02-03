<?php

namespace Callcocam\LaravelRaptor\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class ExportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $fileName;
    protected string $downloadUrl;
    protected string $resourceName;

    public function __construct(string $fileName, string $downloadUrl, string $resourceName = 'registros')
    {
        $this->fileName = $fileName;
        $this->downloadUrl = $downloadUrl;
        $this->resourceName = $resourceName;
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
        return [
            'title' => 'Exportação Concluída',
            'message' => "Sua exportação de {$this->resourceName} foi concluída com sucesso.",
            'type' => 'success',
            'download' => $this->downloadUrl,
            'fileName' => $this->fileName,
            'icon' => '📥',
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
