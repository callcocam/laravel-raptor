# Autenticação de Canais de Broadcasting

Ao usar canais privados de WebSocket, é necessário configurar a autorização para que o Laravel verifique se o usuário tem permissão para acessar o canal.

## Configuração Básica

### routes/channels.php

```php
<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// Canal padrão do Laravel para modelos User
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    $authorized = (string) $user->id === (string) $id;
    
    Log::info('Broadcast channel authorization', [
        'channel' => 'App.Models.User.{id}',
        'user_id' => $user->id,
        'requested_id' => $id,
        'authorized' => $authorized,
    ]);
    
    return $authorized;
});

// Canal privado para notificações do usuário
Broadcast::channel('user.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

// Canal alternativo (usado pelo Raptor)
Broadcast::channel('users.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
```

## Canais com Contexto de Tenant

Para canais que dependem do contexto do tenant/client:

```php
// Canal para sincronização do cliente
Broadcast::channel('sync.client.{id}', function ($user, $id) {
    $currentClientId = config('app.current_client_id');
    
    return $currentClientId && (string) $currentClientId === (string) $id;
});

// Canal do tenant
Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    $currentTenantId = config('app.current_tenant_id');
    
    return $currentTenantId && (string) $currentTenantId === (string) $tenantId;
});
```

## Importante: Comparação de ULIDs

⚠️ **Nunca converta ULIDs para `int`**. Compare sempre como strings:

```php
// ❌ ERRADO - ULIDs não são numéricos
return (int) $user->id === (int) $id;

// ✅ CORRETO - Compare como strings
return (string) $user->id === (string) $id;
```

## Problemas Comuns

### Erro 403 Forbidden

**Causa**: O canal não está definido em `routes/channels.php`.

**Solução**: Adicione a autorização do canal:

```php
Broadcast::channel('nome.do.canal.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});
```

### Canal não Encontrado nos Logs

```
verifyUserCanAccessChannel(): Channel [private-sync.user.xxx] not found
```

**Causa**: A rota de autorização não existe.

**Solução**: Verifique se o nome do canal no `Broadcast::channel()` corresponde exatamente ao canal sendo acessado.

### Logs de Debug

Para diagnosticar problemas, adicione logs nas autorizações:

```php
Broadcast::channel('user.{id}', function ($user, $id) {
    Log::info('Channel auth attempt', [
        'channel' => 'user.{id}',
        'user_id' => $user->id,
        'requested_id' => $id,
        'id_types' => [
            'user_id' => gettype($user->id),
            'requested_id' => gettype($id),
        ],
    ]);
    
    return (string) $user->id === (string) $id;
});
```

## Configuração do Reverb

### .env

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Frontend (app.ts ou bootstrap.ts)

```ts
import { configureEcho } from "@laravel/echo-vue"

configureEcho({
    broadcaster: "reverb",
})
```
