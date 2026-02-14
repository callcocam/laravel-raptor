<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/**
 * Canais de broadcast do pacote Laravel Raptor
 */

// Canal users.{userId} para eventos de import/export
Broadcast::channel('users.{userId}', function ($user, $userId) {
    $authorized = (string) $user->id === (string) $userId;
    Log::info('Broadcast channel authorization', [
        'channel' => 'users.{userId}',
        'user_id' => $user->id,
        'requested_user_id' => $userId,
        'authorized' => $authorized,
    ]);

    return $authorized;
});

// Canal privado para notificações de sincronização do usuário
Broadcast::channel('sync.user.{id}', function ($user, $id) {
    $authorized = (string) $user->id === (string) $id;
    Log::info('Broadcast channel authorization', [
        'channel' => 'sync.user.{id}',
        'user_id' => $user->id,
        'requested_id' => $id,
        'authorized' => $authorized,
    ]);

    return $authorized;
});

// Canal privado para notificações de sincronização do cliente
Broadcast::channel('sync.client.{id}', function ($user, $id) {
    // Verifica se o usuário tem acesso ao cliente através do contexto atual
    $currentClientId = config('app.current_domainable_id');
    $authorized = $currentClientId && (string) $currentClientId === (string) $id;
    Log::info('Broadcast channel authorization', [
        'channel' => 'sync.client.{id}',
        'user_id' => $user->id,
        'current_client_id' => $currentClientId,
        'requested_client_id' => $id,
        'authorized' => $authorized,
    ]);

    return $authorized;
});
