<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Notifications\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;
use ReflectionClass;

/**
 * Canal de notificação que adiciona tenant_id e client_id
 * como colunas separadas na tabela notifications
 */
class TenantAwareDatabaseChannel extends DatabaseChannel
{
    /**
     * Build an array payload for the DatabaseNotification Model.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        $payload = parent::buildPayload($notifiable, $notification);
        
        // Adiciona tenant_id e client_id como colunas separadas
        $payload['tenant_id'] = $this->getTenantId($notification);
        $payload['client_id'] = $this->getClientId($notification);
        
        return $payload;
    }
    
    /**
     * Obtém o tenant_id da notificação ou do contexto atual
     */
    protected function getTenantId(Notification $notification): ?string
    {
        // Tenta obter via método getter se existir
        if (method_exists($notification, 'getTenantId')) {
            return $notification->getTenantId();
        }
        
        // Tenta obter via reflection para propriedades protected
        $value = $this->getProtectedProperty($notification, 'tenantId');
        if ($value !== null) {
            return $value;
        }
        
        // Fallback para config
        return config('app.current_tenant_id');
    }
    
    /**
     * Obtém o client_id da notificação ou do contexto atual
     */
    protected function getClientId(Notification $notification): ?string
    {
        // Tenta obter via método getter se existir
        if (method_exists($notification, 'getClientId')) {
            return $notification->getClientId();
        }
        
        // Tenta obter via reflection para propriedades protected
        $value = $this->getProtectedProperty($notification, 'clientId');
        if ($value !== null) {
            return $value;
        }
        
        // Fallback para config
        return config('app.current_client_id');
    }
    
    /**
     * Obtém uma propriedade protected/private via reflection
     */
    protected function getProtectedProperty(object $object, string $property): mixed
    {
        try {
            $reflection = new ReflectionClass($object);
            
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);
                return $prop->getValue($object);
            }
        } catch (\Throwable) {
            // Ignora erros de reflection
        }
        
        return null;
    }
}
