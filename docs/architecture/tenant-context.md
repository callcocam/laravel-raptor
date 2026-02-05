# Tenant em Jobs e Commands

Jobs em filas e Commands do Artisan rodam em processos separados da requisição HTTP. Por isso, o contexto do tenant precisa ser capturado e restaurado manualmente.

## O Problema

```php
// No controller (HTTP) - tenant está disponível
$tenant = app('tenant'); // ✅ Funciona

// No Job (queue worker) - contexto perdido
class ProcessExport implements ShouldQueue
{
    public function handle()
    {
        $tenant = app('tenant'); // ❌ NULL - processo diferente!
    }
}
```

## Solução: TenantAwareJob

Use o trait `TenantAwareJob` para capturar e restaurar o contexto automaticamente:

```php
<?php

namespace App\Jobs;

use Callcocam\LaravelRaptor\Traits\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use TenantAwareJob; // 👈 Adicione o trait

    public function __construct(
        protected array $data
    ) {
        // Captura o contexto do tenant no momento do dispatch
        $this->captureTenantContext();
    }

    public function handle(): void
    {
        // Restaura o contexto do tenant no worker
        $this->restoreTenantContext();
        
        // Agora pode usar normalmente
        $tenant = app('tenant'); // ✅ Funciona!
        $tenantId = config('app.current_tenant_id'); // ✅ Funciona!
        
        // Seu código aqui...
    }
}
```

### Propriedades Capturadas

O trait captura automaticamente:

| Propriedade | Descrição |
|-------------|-----------|
| `$tenantId` | ID do tenant atual |
| `$domainableId` | ID do domainable (client, store) |
| `$domainableType` | Tipo do domainable |

### Como Funciona

1. **No dispatch** (HTTP): `captureTenantContext()` serializa o tenant e configs
2. **No worker** (queue): `restoreTenantContext()` restaura o contexto
3. O `TenantContext` helper é atualizado para uso global

## Solução: TenantAwareCommand

Para commands do Artisan, use o trait `TenantAwareCommand`:

```php
<?php

namespace App\Console\Commands;

use Callcocam\LaravelRaptor\Traits\TenantAwareCommand;
use Illuminate\Console\Command;

class ProcessOrders extends Command
{
    use TenantAwareCommand;

    protected $signature = 'orders:process {--tenant= : ID ou domínio do tenant}';
    protected $description = 'Processa pedidos de um tenant';

    public function handle(): int
    {
        // Configura o tenant pelo argumento --tenant
        if (!$this->setupTenantFromOption()) {
            return Command::FAILURE;
        }
        
        // Agora pode usar normalmente
        $tenant = app('tenant');
        $this->info("Processando para tenant: {$tenant->name}");
        
        // Seu código aqui...
        
        return Command::SUCCESS;
    }
}
```

### Executar

```bash
# Por ID
php artisan orders:process --tenant=01kgjmcjhz37gfaejrrkbb5ks7

# Por domínio
php artisan orders:process --tenant=tenant.example.com
```

### Iterar Sobre Todos os Tenants

```php
public function handle(): int
{
    $this->forEachTenant(function ($tenant) {
        $this->info("Processando: {$tenant->name}");
        
        // Seu código aqui - contexto do tenant já configurado
        $products = Product::count();
        $this->line("  - {$products} produtos");
    });
    
    return Command::SUCCESS;
}
```

## TenantContext Helper

O `TenantContext` é um helper global que funciona em qualquer lugar:

```php
use Callcocam\LaravelRaptor\Support\TenantContext;

// Verificar tenant atual
$tenantId = TenantContext::id();
$context = TenantContext::current(); // Array com todos os dados

// Executar código no contexto de um tenant
TenantContext::run($tenant, function () {
    // Código executa com contexto do tenant
    $products = Product::all();
});

// Iterar sobre todos os tenants
TenantContext::forAll(function ($tenant) {
    // Executa para cada tenant ativo
});

// Serializar/Deserializar (útil para jobs)
$serialized = TenantContext::serialize();
TenantContext::unserialize($serialized);
```

## Exemplo Completo: Job de Exportação

```php
<?php

namespace App\Jobs;

use Callcocam\LaravelRaptor\Traits\TenantAwareJob;
use Callcocam\LaravelRaptor\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use TenantAwareJob;

    public function __construct(
        protected string $modelClass,
        protected array $filters,
        protected int|string $userId
    ) {
        $this->captureTenantContext();
    }

    public function handle(): void
    {
        $this->restoreTenantContext();

        Log::info("Exportação iniciada", [
            'tenantId' => $this->tenantId,
            'tenant' => TenantContext::current(),
            'userId' => $this->userId,
        ]);

        // Query já terá o scope do tenant aplicado automaticamente
        $query = app($this->modelClass)->newQuery();
        
        foreach ($this->filters as $column => $value) {
            $query->where($column, $value);
        }

        $data = $query->get();
        
        // Processa exportação...
        
        Log::info("Exportação concluída", [
            'total' => $data->count(),
            'tenantId' => TenantContext::id(),
        ]);
    }
}
```

## Logs de Debug

Ao usar o `TenantAwareJob`, você pode adicionar logs para verificar o contexto:

```php
Log::info("Contexto do tenant", [
    'tenantId (job)' => $this->tenantId,
    'domainableId (job)' => $this->domainableId,
    'TenantContext::id()' => TenantContext::id(),
    'TenantContext::current()' => TenantContext::current(),
    'config(app.current_tenant_id)' => config('app.current_tenant_id'),
]);
```

Saída esperada:
```json
{
    "tenantId (job)": "01kgjmcjhz37gfaejrrkbb5ks7",
    "TenantContext::id()": "01kgjmcjhz37gfaejrrkbb5ks7",
    "TenantContext::current()": {
        "Callcocam\\LaravelRaptor\\Models\\Tenant": {
            "id": "01kgjmcjhz37gfaejrrkbb5ks7",
            "name": "Tenant - Área do Cliente",
            "domain": "tenant.example.com"
        }
    }
}
```
