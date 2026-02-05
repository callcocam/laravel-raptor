# Sistema de Notificações Global - Guia de Uso

O sistema de notificações foi refatorado para ser **plugável e extensível**. Agora você pode registrar handlers customizados para qualquer evento sem modificar o código principal.

## Arquitetura

```
┌─────────────────────────────────────────────────────────────┐
│                    WebSocket (Echo)                         │
│  Canais: App.Models.User.{id}, user.{id}, users.{id}       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Event Router                             │
│  1. Busca handler específico (string/regex)                │
│  2. Se não encontrar, usa normalização genérica            │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                 Notification Store                          │
│  - Gerencia lista de notificações                          │
│  - Exibe toasts                                             │
│  - Sincroniza com backend                                   │
└─────────────────────────────────────────────────────────────┘
```

## Uso Básico

### Registrar Handler para Evento Específico

```ts
import { registerEventHandler } from '@/composables/useGlobalNotifications'

// Handler para evento específico
registerEventHandler('order.created', (eventName, payload) => ({
    id: `order-${payload.order?.id}-${Date.now()}`,
    type: 'success',
    title: 'Novo Pedido!',
    message: `Pedido #${payload.order?.id} foi criado`,
    data: payload,
    read_at: null,
    created_at: new Date().toISOString(),
}))
```

### Registrar Handler com Regex (Múltiplos Eventos)

```ts
// Handler para múltiplos eventos de pedido
registerEventHandler(/^order\.(created|updated|shipped|delivered)$/, (eventName, payload) => {
    const action = eventName.split('.')[1] // 'created', 'updated', etc.
    
    const titles: Record<string, string> = {
        created: 'Novo Pedido',
        updated: 'Pedido Atualizado',
        shipped: 'Pedido Enviado',
        delivered: 'Pedido Entregue',
    }
    
    return {
        id: `order-${payload.order?.id}-${Date.now()}`,
        type: action === 'delivered' ? 'success' : 'info',
        title: titles[action] || 'Atualização de Pedido',
        message: `Pedido #${payload.order?.id}`,
        data: payload,
        read_at: null,
        created_at: new Date().toISOString(),
    }
})
```

### Retornar `null` para Ignorar Evento

```ts
registerEventHandler('heartbeat.ping', () => {
    // Retorna null para ignorar eventos de heartbeat
    return null
})
```

### Registrar Toast Customizado

```ts
import { registerToastHandler } from '@/composables/useGlobalNotifications'

// Toast especial para exportações com botão de download
registerToastHandler('export', (notification) => ({
    duration: 10000, // 10 segundos
    action: {
        label: 'Download',
        onClick: () => {
            if (notification.data?.downloadUrl) {
                window.location.href = notification.data.downloadUrl
            }
        },
    },
}))
```

## Criar Notificações Manualmente

```ts
import { notify, createNotification } from '@/composables/useGlobalNotifications'

// Forma simples
notify('success', 'Operação concluída!', 'Seus dados foram salvos')

// Com dados extras
notify('error', 'Erro de validação', 'Verifique os campos', {
    fields: ['email', 'name'],
    error_code: 422,
})

// Criar objeto de notificação (útil para testes)
const notification = createNotification('info', 'Título', 'Mensagem', { custom: 'data' })
```

## Usando no Backend (Laravel)

### Evento Simples

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderCreated implements ShouldBroadcast
{
    public function __construct(
        public Order $order
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->order->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.created'; // Nome do evento no frontend
    }

    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'total' => $this->order->total,
                'status' => $this->order->status,
            ],
        ];
    }
}
```

### Disparar Evento

```php
// No controller ou serviço
event(new OrderCreated($order));

// Ou usando broadcast helper
broadcast(new OrderCreated($order))->toOthers();
```

## Handlers Built-in

O sistema já vem com handlers para os seguintes eventos:

| Evento | Descrição |
|--------|-----------|
| `import.completed` | Importação de dados finalizada |
| `export.completed` | Exportação de dados finalizada (com link de download) |
| `database.connection.failed` | Erro de conexão com banco de dados |

## Status de Conexão

O sistema usa `useConnectionStatus` do `@laravel/echo-vue` para monitorar a conexão WebSocket:

```vue
<script setup>
import { useGlobalNotifications } from '@/composables/useGlobalNotifications'

const { connectionStatus, isConnected } = useGlobalNotifications()
// connectionStatus: 'connected' | 'disconnected' | 'connecting' | 'reconnecting' | 'failed'
// isConnected: boolean (computed)
</script>

<template>
    <span v-if="isConnected" class="text-green-500">● Conectado</span>
    <span v-else class="text-red-500">● Desconectado</span>
</template>
```

## Cleanup de Handlers

```ts
import { registerEventHandler, clearAllHandlers } from '@/composables/useGlobalNotifications'

// Registrar handler e guardar função de cleanup
const unregister = registerEventHandler('my.event', handler)

// Remover handler específico
unregister()

// Remover TODOS os handlers (incluindo built-in)
clearAllHandlers()
```

## Eventos Escutados Automaticamente

O sistema escuta os seguintes canais e eventos automaticamente:

### Canais
- `App.Models.User.{userId}` - Canal padrão do Laravel para notificações de model
- `user.{userId}` - Canal do usuário para eventos customizados
- `users.{userId}` - Canal alternativo (import/export)

### Eventos
- `.Illuminate\Notifications\Events\BroadcastNotificationCreated`
- `.notification.created`
- `.notification`
- `.import.completed`
- `.export.completed`
- `.notification.updated`
- `.database.connection.failed`

## Normalização Automática

Se um evento não tiver um handler específico, o sistema tenta normalizar o payload automaticamente procurando por:

1. `payload.notification` ou `payload.data` ou `payload` direto
2. Campos: `id`, `type`, `title`, `message`, `description`, `body`, `created_at`, `timestamp`

Isso significa que **qualquer evento com uma estrutura mínima será convertido em notificação automaticamente**.
