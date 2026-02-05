# Echo Vue Helpers

O pacote `@laravel/echo-vue` fornece composables para integração do Laravel Echo com Vue.js.

## Configuração

Chame `configureEcho` antes de usar os composables:

```ts
import { configureEcho } from "@laravel/echo-vue"

configureEcho({
    broadcaster: "reverb",
})
```

Para Reverb, os valores padrão são preenchidos automaticamente:

```ts
{
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
}
```

## useConnectionStatus

Retorna o status reativo da conexão WebSocket:

```vue
<script setup>
import { useConnectionStatus } from "@laravel/echo-vue"

const status = useConnectionStatus()

const getStatusColor = (status) => {
    switch (status) {
        case "connected": return "green"
        case "connecting": return "yellow"
        case "reconnecting": return "orange"
        case "failed": return "red"
        default: return "gray"
    }
}
</script>

<template>
    <div :style="{ color: getStatusColor(status) }">
        Conexão: {{ status }}
    </div>
</template>
```

### Valores Possíveis

| Status | Descrição |
|--------|-----------|
| `connected` | Conectado ao servidor WebSocket |
| `disconnected` | Desconectado |
| `connecting` | Tentando conectar (inicial) |
| `reconnecting` | Reconectando após desconexão |
| `failed` | Falha na conexão, não vai tentar novamente |

## useEcho

Conecta a um canal e escuta eventos:

```ts
import { useEcho } from "@laravel/echo-vue"

const { leaveChannel, leave, stopListening, listen } = useEcho(
    `orders.${orderId}`,       // Nome do canal
    "OrderShipmentUpdated",    // Nome do evento
    (e) => {                   // Callback
        console.log(e.order)
    }
)

// Parar de escutar sem sair do canal
stopListening()

// Voltar a escutar
listen()

// Sair do canal
leaveChannel()

// Sair do canal e canais relacionados (private, presence)
leave()
```

### Canal Privado

```ts
// Canais que começam com "private-" são privados
useEcho(
    `private-user.${userId}`,
    "NotificationReceived",
    (notification) => {
        console.log("Nova notificação:", notification)
    }
)
```

### Múltiplos Eventos

```ts
// Para múltiplos eventos, use múltiplas chamadas
useEcho(`orders.${orderId}`, "OrderCreated", handleCreated)
useEcho(`orders.${orderId}`, "OrderUpdated", handleUpdated)
useEcho(`orders.${orderId}`, "OrderShipped", handleShipped)
```

## echo()

Acesso direto à instância do Echo:

```ts
import { echo } from "@laravel/echo-vue"

// Verificar status
const status = echo().connectionStatus()

// Acessar instância diretamente
const echoInstance = echo()
```

## Exemplo Completo

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useEcho, useConnectionStatus } from '@laravel/echo-vue'

const props = defineProps<{
    userId: string
}>()

const notifications = ref<any[]>([])
const status = useConnectionStatus()

// Escutar eventos de exportação
const { listen: listenExport } = useEcho(
    `private-users.${props.userId}`,
    'export.completed',
    (data) => {
        notifications.value.unshift({
            id: Date.now(),
            type: 'success',
            title: 'Exportação Concluída',
            message: data.message,
            downloadUrl: data.downloadUrl,
        })
    }
)

// Escutar eventos de importação
const { listen: listenImport } = useEcho(
    `private-users.${props.userId}`,
    'import.completed',
    (data) => {
        notifications.value.unshift({
            id: Date.now(),
            type: data.failed > 0 ? 'warning' : 'success',
            title: 'Importação Concluída',
            message: data.message,
        })
    }
)

onMounted(() => {
    // Ativa os listeners
    listenExport()
    listenImport()
})
</script>

<template>
    <div>
        <!-- Status da conexão -->
        <div class="flex items-center gap-2 mb-4">
            <span 
                class="w-2 h-2 rounded-full"
                :class="{
                    'bg-green-500': status === 'connected',
                    'bg-yellow-500': status === 'connecting',
                    'bg-red-500': status === 'disconnected' || status === 'failed',
                }"
            />
            <span class="text-sm text-muted-foreground">
                {{ status === 'connected' ? 'Online' : 'Offline' }}
            </span>
        </div>

        <!-- Lista de notificações -->
        <div v-for="notification in notifications" :key="notification.id">
            <div :class="`alert alert-${notification.type}`">
                <strong>{{ notification.title }}</strong>
                <p>{{ notification.message }}</p>
                <a 
                    v-if="notification.downloadUrl" 
                    :href="notification.downloadUrl"
                    class="btn btn-sm"
                >
                    Download
                </a>
            </div>
        </div>
    </div>
</template>
```
