# Sistema Multi-Tenancy

O Laravel Raptor oferece um sistema flexível de multi-tenancy com suporte a diferentes estratégias de isolamento.

## Visão Geral

```
┌─────────────────────────────────────────────────────────────┐
│                      Request HTTP                           │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    TenantResolver                           │
│  Detecta o tenant pelo domínio/subdomínio                  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      Landlord                               │
│  Aplica scopes automáticos em queries                      │
└─────────────────────────────────────────────────────────────┘
```

## TenantResolver

O `TenantResolver` é responsável por identificar o tenant a partir da requisição. Por padrão, ele verifica a tabela `tenants` pelo campo `domain`.

### Configuração

```php
// config/raptor.php
'services' => [
    // Resolver padrão (apenas tabela tenants)
    'tenant_resolver' => \Callcocam\LaravelRaptor\Services\TenantResolver::class,
    
    // Ou resolver customizado
    'tenant_resolver' => \App\Services\AdvancedTenantResolver::class,
],
```

Para **configurar e implementar um TenantResolver personalizado** (interface, cuidados, exemplos com Client/Store e banco dedicado), veja [Custom TenantResolver](custom-tenant-resolver.md).

### Resolver Padrão

O resolver padrão é simples e verifica apenas a tabela `tenants`:

```php
// Callcocam\LaravelRaptor\Services\TenantResolver

public function detectTenant(string $host): ?object
{
    $tenant = Tenant::where('domain', $host)
        ->where('status', 'published')
        ->first();
        
    if ($tenant) {
        $this->storeTenantContext($tenant);
    }
    
    return $tenant;
}
```

### Criar Resolver Customizado

Para cenários mais complexos (ex: tenant_domains, client, store), crie seu próprio resolver:

```php
<?php

namespace App\Services;

use Callcocam\LaravelRaptor\Contracts\TenantResolverInterface;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;

class AdvancedTenantResolver implements TenantResolverInterface
{
    protected ?object $tenant = null;
    protected ?object $domainable = null;
    protected bool $resolved = false;

    public function resolve(string $host): ?object
    {
        if ($this->resolved) {
            return $this->tenant;
        }

        // 1. Busca pelo domínio exato na tabela tenants
        $tenant = \App\Models\Tenant::where('domain', $host)
            ->where('status', 'published')
            ->first();
            
        if ($tenant) {
            $this->tenant = $tenant;
            $this->resolved = true;
            $this->storeTenantContext($tenant);
            return $tenant;
        }

        // 2. Busca na tabela tenant_domains (polymorphic)
        $domain = \App\Models\TenantDomain::where('domain', $host)
            ->where('status', 'published')
            ->first();
            
        if ($domain && $domain->domainable) {
            $this->domainable = $domain->domainable;
            
            // Determina o tenant baseado no tipo
            if ($domain->domainable_type === 'App\\Models\\Client') {
                $this->tenant = $domain->domainable->tenant;
                config(['app.current_client_id' => $domain->domainable->id]);
            } elseif ($domain->domainable_type === 'App\\Models\\Store') {
                $this->tenant = $domain->domainable->client->tenant;
                config(['app.current_store_id' => $domain->domainable->id]);
                config(['app.current_client_id' => $domain->domainable->client_id]);
            }
            
            $this->resolved = true;
            $this->storeTenantContext($this->tenant, $domain);
            return $this->tenant;
        }

        $this->resolved = true;
        return null;
    }

    public function getTenant(): ?object
    {
        return $this->tenant;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function storeTenantContext(mixed $tenant, ?object $domainData = null): void
    {
        app()->instance('tenant', $tenant);
        app()->instance('current.tenant', $tenant);
        
        config(['app.current_tenant_id' => $tenant->id]);
        config(['app.context' => 'tenant']);
        
        Landlord::addTenant($tenant);
        
        if ($domainData && $domainData->domainable) {
            app()->instance('current.domainable', $domainData->domainable);
            config(['app.current_domainable_type' => $domainData->domainable_type]);
            config(['app.current_domainable_id' => $domainData->domainable_id]);
        }
    }

    public function configureTenantDatabase(mixed $tenant, ?object $domainData = null): void
    {
        if (!$tenant->database) {
            return;
        }

        $connectionName = "tenant_{$tenant->id}";
        
        config(["database.connections.{$connectionName}" => [
            'driver' => 'pgsql',
            'host' => config('database.connections.pgsql.host'),
            'database' => $tenant->database,
            'username' => config('database.connections.pgsql.username'),
            'password' => config('database.connections.pgsql.password'),
        ]]);

        \DB::purge($connectionName);
    }
}
```

## Interface TenantResolverInterface

Qualquer resolver customizado deve implementar a interface:

```php
interface TenantResolverInterface
{
    /**
     * Resolve o tenant a partir do host
     */
    public function resolve(string $host): ?object;
    
    /**
     * Retorna o tenant atual
     */
    public function getTenant(): ?object;
    
    /**
     * Verifica se já foi resolvido
     */
    public function isResolved(): bool;
    
    /**
     * Armazena o contexto do tenant
     */
    public function storeTenantContext(mixed $tenant, ?object $domainData = null): void;
    
    /**
     * Configura banco de dados separado
     */
    public function configureTenantDatabase(mixed $tenant, ?object $domainData = null): void;
}
```

## Landlord (Scopes Automáticos)

O Landlord aplica automaticamente filtros de tenant em todas as queries:

```php
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;

// Adicionar tenant ao scope
Landlord::addTenant($tenant);

// Models com trait BelongsToTenants serão filtrados automaticamente
$products = Product::all(); // WHERE tenant_id = '...'

// Desabilitar temporariamente
Landlord::disable();
$allProducts = Product::all(); // Sem filtro

// Reabilitar
Landlord::enable();
```

## Acessar Contexto do Tenant

```php
// Via helper
$tenant = app('tenant');
$tenantId = config('app.current_tenant_id');
$clientId = config('app.current_client_id');

// Via TenantContext (funciona em Jobs e Commands)
use Callcocam\LaravelRaptor\Support\TenantContext;

$tenantId = TenantContext::id();
$context = TenantContext::current();
```

## Estratégias de Banco de Dados

### Banco Compartilhado (Padrão)

Todos os tenants usam o mesmo banco, isolados por `tenant_id`:

```php
// config/raptor.php
'database' => [
    'strategy' => 'shared',
],
```

### Banco Separado

Cada tenant tem seu próprio banco de dados:

```php
// config/raptor.php
'database' => [
    'strategy' => 'separate',
],
```

Neste caso, o campo `database` do tenant deve conter o nome do banco.
