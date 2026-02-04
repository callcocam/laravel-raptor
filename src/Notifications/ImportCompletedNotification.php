<?php

namespace Callcocam\LaravelRaptor\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ImportCompletedNotification extends Notification
{
    use Queueable;

    protected string $resourceName;
    protected bool $wasQueued;

    public function __construct(string $resourceName = 'registros', bool $wasQueued = false)
    {
        $this->resourceName = $resourceName;
        $this->wasQueued = $wasQueued;
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
