<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Traits;

use Illuminate\Notifications\Notification;

/**
 * Trait para jobs que precisam notificar o usuário ao concluir (export, import, etc.).
 * A classe que usar deve ter a propriedade userId (int|string).
 */
trait NotifiesUserOnCompletion
{
    /**
     * Busca o usuário pelo userId e envia a notificação, se existir.
     */
    protected function notifyUser(Notification $notification): void
    {
        $user = \App\Models\User::find($this->userId);
        if ($user) {
            $user->notify($notification);
        }
    }
}
