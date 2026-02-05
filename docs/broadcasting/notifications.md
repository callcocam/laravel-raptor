# Sistema de Notificações em Tempo Real

O Laravel Raptor oferece um sistema de notificações plugável e extensível usando WebSocket (Laravel Reverb) e Vue.js.

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
    const action = eventName.split('.')[1]
    
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

## Notificações com Contexto de Tenant

As notificações automaticamente incluem o contexto do tenant e client nos dados:

### Backend - Notification

```php
use Callcocam\LaravelRaptor\Notifications\ExportCompletedNotification;

// Os campos tenant_id, tenant_name, client_id, client_name
// são capturados automaticamente do contexto atual
$user->notify(new ExportCompletedNotification(
    fileName: 'produtos.xlsx',
    downloadUrl: route('download.export', $filename),
    resourceName: 'produtos',
    wasQueued: true
));
```

### Backend - Event Broadcast

```php
use Callcocam\LaravelRaptor\Events\ExportCompleted;

// O evento também captura o contexto automaticamente
event(new ExportCompleted(
    userId: $user->id,
    modelName: 'Product',
    totalRows: 100,
    filePath: 'exports/produtos.xlsx',
    fileName: 'produtos.xlsx'
));
```

### Dados Enviados

O `broadcastWith()` inclui automaticamente:

```json
{
    "type": "export",
    "model": "Product",
    "total": 100,
    "downloadUrl": "/download-export/produtos.xlsx",
    "message": "Exportação concluída: 100 registros exportados",
    "timestamp": "2026-02-05T15:23:38.000000Z",
    "tenant_id": "01kgjmcjhz37gfaejrrkbb5ks7",
    "tenant_name": "Tenant - Área do Cliente",
    "client_id": null,
    "client_name": null
}
```

## Colunas na Tabela Notifications (Opcional)

Por padrão, os dados de tenant/client ficam no JSON `data`. Opcionalmente, você pode criar colunas separadas para melhor performance em queries:

### Migration (Opcional)

```bash
php artisan make:migration add_tenant_columns_to_notifications_table
```

```php
public function up(): void
{
    Schema::table('notifications', function (Blueprint $table) {
        $table->ulid('tenant_id')->nullable()->after('notifiable_id')->index();
        $table->ulid('client_id')->nullable()->after('tenant_id')->index();
        
        $table->index(['tenant_id', 'notifiable_type', 'notifiable_id']);
    });
}
```

O sistema detecta automaticamente se as colunas existem:
- **Colunas existem**: Salva nos campos separados
- **Colunas não existem**: Usa apenas o JSON `data`

### Consultar por Tenant

```php
// Se as colunas existirem
$notifications = DB::table('notifications')
    ->where('tenant_id', config('app.current_tenant_id'))
    ->get();

// Ou pelo JSON data
$notifications = DB::table('notifications')
    ->whereJsonContains('data->tenant_id', config('app.current_tenant_id'))
    ->get();
```

## Criar Notificação Customizada

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification
{
    protected ?string $tenantId;
    protected ?string $clientId;

    public function __construct(
        protected Order $order
    ) {
        // Captura contexto automaticamente
        $this->tenantId = config('app.current_tenant_id');
        $this->clientId = config('app.current_client_id');
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Novo Pedido',
            'message' => "Pedido #{$this->order->id} foi criado",
            'type' => 'success',
            'icon' => '🛒',
            'order_id' => $this->order->id,
            'tenant_id' => $this->tenantId,
            'client_id' => $this->clientId,
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
```

## Criar Evento de Broadcast Customizado

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class OrderCreated implements ShouldBroadcastNow
{
    public ?string $tenantId;
    public ?string $clientId;

    public function __construct(
        public int|string $userId,
        public Order $order
    ) {
        $this->tenantId = config('app.current_tenant_id');
        $this->clientId = config('app.current_client_id');
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.' . $this->userId)];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'order',
            'order_id' => $this->order->id,
            'total' => $this->order->total,
            'message' => "Novo pedido #{$this->order->id}",
            'tenant_id' => $this->tenantId,
            'client_id' => $this->clientId,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }
}
```

## Frontend - Consumir o Evento

```ts
// Registrar handler no frontend
registerEventHandler('order.created', (eventName, payload) => ({
    id: `order-${payload.order_id}-${Date.now()}`,
    type: 'success',
    title: 'Novo Pedido!',
    message: payload.message,
    data: {
        ...payload,
        tenant_id: payload.tenant_id,
        client_id: payload.client_id,
    },
    read_at: null,
    created_at: payload.timestamp,
}))
```

## Status de Conexão

Use o composable `useConnectionStatus` para exibir o status da conexão WebSocket:

```vue
<script setup>
import { useConnectionStatus } from "@laravel/echo-vue"

const status = useConnectionStatus()

const statusConfig = {
    connected: { color: 'green', text: 'Conectado' },
    connecting: { color: 'yellow', text: 'Conectando...' },
    disconnected: { color: 'red', text: 'Desconectado' },
    failed: { color: 'red', text: 'Falha na conexão' },
}
</script>

<template>
    <div :class="`text-${statusConfig[status].color}-500`">
        {{ statusConfig[status].text }}
    </div>
</template>
```
