# Configuração de Jobs e Commands com contexto Tenant

Este documento descreve como configurar **jobs** e **commands** para rodar no contexto do tenant, incluindo a troca automática da conexão de banco (quando o tenant tem database dedicado), **sem precisar passar configuração de banco** manualmente.

---

## Configuração (config e env)

No `config/raptor.php`, na chave `database`:

| Chave | Env | Padrão | Descrição |
|-------|-----|--------|-----------|
| `strategy` | `RAPTOR_DB_STRATEGY` | `shared` | Estratégia: `shared` (um DB) ou `separate` (DB por tenant). |
| `configure_in_jobs` | `RAPTOR_DB_CONFIGURE_IN_JOBS` | `true` | Se `true`, ao restaurar o contexto no job a conexão default (e a nomeada `tenant`) passam a apontar para o banco do tenant. |
| `configure_in_commands` | `RAPTOR_DB_CONFIGURE_IN_COMMANDS` | `true` | Se `true`, ao usar `setupTenantContext` no command a conexão default (e `tenant`) passam a apontar para o banco do tenant. |

**Desativar apenas em jobs** (ex.: worker sempre no banco principal):

```env
RAPTOR_DB_CONFIGURE_IN_JOBS=false
```

**Desativar apenas em commands**:

```env
RAPTOR_DB_CONFIGURE_IN_COMMANDS=false
```

Quando desativado, o contexto do tenant (app/tenant, config `current_tenant_id`, etc.) continua sendo restaurado; só não é feita a troca da conexão de banco.

---

## Jobs (TenantAwareJob)

### O que o trait faz

- **No dispatch:** captura `tenant_id`, `domainable_type` e `domainable_id` do contexto atual (config).
- **Na execução:** restaura o tenant, aplica `ResolvedTenantConfig` e, se `configure_in_jobs` estiver ativo, aplica o banco do tenant na conexão default (e na conexão `tenant`).

Você **não** precisa passar conexão nem config de banco para o job; o worker resolve pelo tenant capturado.

### Uso básico

1. Use o trait `TenantAwareJob`.
2. No **construtor**, chame `$this->captureTenantContext()`.
3. No **middleware** do job, retorne `$this->tenantMiddleware()` para restaurar o contexto (e o banco) antes do `handle()`.

```php
<?php

namespace App\Jobs;

use Callcocam\LaravelRaptor\Traits\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use TenantAwareJob;

    public function __construct(
        protected Order $order
    ) {
        $this->captureTenantContext();
    }

    public function middleware(): array
    {
        return $this->tenantMiddleware();
    }

    public function handle(): void
    {
        // Contexto e banco do tenant já estão ativos
        $tenant = app('tenant');
        $order = Order::find($this->order->id); // usa a conexão default = banco do tenant
    }
}
```

### Pontos importantes

- **Sempre** chame `captureTenantContext()` no construtor quando o job for disparado no contexto de um tenant (ex.: a partir de uma requisição já resolvida).
- **Sempre** use `tenantMiddleware()` no `middleware()` do job para restaurar o contexto (e o banco) no worker.
- Não é necessário passar `connectionName` nem `connectionConfig` para o job; a aplicação usa `ResolvedTenantConfig` e `TenantDatabaseManager::applyConfig()` internamente.

### Propriedades serializadas

O trait expõe (e serializa na fila):

- `$tenantId`
- `$domainableId`
- `$domainableType`

Assim o worker consegue recriar o contexto e o banco do tenant.

---

## Commands (TenantAwareCommand)

### O que o trait faz

- Oferece opção `--tenant` (ID ou domínio) e métodos para configurar o contexto.
- Ao chamar `setupTenantContext($tenant)` ou `setupTenantFromOption()`, aplica `ResolvedTenantConfig` e, se `configure_in_commands` estiver ativo, aplica o banco do tenant na conexão default (e `tenant`).

Você **não** precisa configurar conexão manualmente; o command usa a mesma lógica centralizada.

### Uso com opção --tenant obrigatória

```php
<?php

namespace App\Console;

use Callcocam\LaravelRaptor\Traits\TenantAwareCommand;
use Illuminate\Console\Command;

class ProcessOrdersCommand extends Command
{
    use TenantAwareCommand;

    protected $signature = 'orders:process {--tenant= : ID ou domínio do tenant}';

    public function handle(): int
    {
        if (! $this->setupTenantFromOption()) {
            return self::FAILURE;
        }

        $this->info('Processando para tenant: ' . app('tenant')->name);
        // Models usam a conexão default = banco do tenant (se tiver database dedicado)

        return self::SUCCESS;
    }
}
```

### Uso com opção --tenant opcional

```php
if (! $this->setupTenantFromOptionIfProvided()) {
    // Nenhum --tenant informado; rode no banco default (ex.: landlord)
}
```

### Iterar sobre todos os tenants

```php
public function handle(): int
{
    $this->forEachTenant(function ($tenant) {
        $this->info("Processando: {$tenant->name}");
        // Contexto e banco já são do tenant atual
    });

    return self::SUCCESS;
}
```

Entre cada iteração o trait chama `clearTenantContext()` e depois `setupTenantContext($tenant)` para o próximo.

### Identificação do tenant

O command aceita `--tenant` como:

- **ID** do tenant (UUID ou numérico), ou  
- **Domínio** (valor da coluna configurada em `raptor.tenant.subdomain_column`, normalmente `domain`).

---

## Resumo

| Onde | O que configurar | Passar conexão/banco? |
|------|------------------|------------------------|
| **Job** | `TenantAwareJob` + `captureTenantContext()` no construtor + `tenantMiddleware()` no `middleware()` | Não |
| **Command** | `TenantAwareCommand` + `setupTenantFromOption()` ou `forEachTenant()` | Não |
| **Config** | `raptor.database.configure_in_jobs` e `configure_in_commands` (default `true`) | — |

Tanto jobs quanto commands usam internamente `ResolvedTenantConfig` e `TenantDatabaseManager::applyConfig()`, o mesmo fluxo da requisição HTTP. Assim, a conexão default (e a nomeada `tenant`) passam a apontar para o banco do tenant quando ele tem `database` preenchido, e você não precisa repassar configuração de banco para jobs nem para commands.
