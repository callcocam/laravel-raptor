<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Notifications\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

/**
 * Canal de notificação que adiciona tenant_id e client_id
 * como colunas separadas na tabela notifications (se existirem)
 * 
 * Se as colunas não existirem, os dados já estão no JSON 'data'
 * através do toDatabase() das notificações.
 */
class TenantAwareDatabaseChannel extends DatabaseChannel
{
    /**
     * Cache para verificar se as colunas existem
     */
    protected static ?bool $hasTenantColumn = null;
    protected static ?bool $hasClientColumn = null;
    
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
        
        // Só adiciona as colunas se elas existirem na tabela
        if ($this->hasTenantColumn()) {
            $payload['tenant_id'] = $this->getTenantId($notification);
        }
        
        if ($this->hasClientColumn()) {
            $payload['client_id'] = $this->getClientId($notification);
        }
        
        return $payload;
    }
    
    /**
     * Verifica se a coluna tenant_id existe na tabela notifications
     */
    protected function hasTenantColumn(): bool
    {
        if (self::$hasTenantColumn === null) {
            self::$hasTenantColumn = Schema::hasColumn('notifications', 'tenant_id');
        }
        
        return self::$hasTenantColumn;
    }
    
    /**
     * Verifica se a coluna client_id existe na tabela notifications
     */
    protected function hasClientColumn(): bool
    {
        if (self::$hasClientColumn === null) {
            self::$hasClientColumn = Schema::hasColumn('notifications', 'client_id');
        }
        
        return self::$hasClientColumn;
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
