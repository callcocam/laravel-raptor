# Sistema de Rotas Multi-Tenant

## Visão Geral

O Laravel Raptor implementa um sistema de rotas baseado em domínios/subdomínios que suporta três contextos distintos:

## 1. 🌐 Domínio Principal (Site da Aplicação)

**Acesso:** `example.com` (domínio sem subdomínio)

**Propósito:** Site institucional, landing page, marketing, documentação

**Arquivo de Rotas:** `routes/site.php`

**Exemplos:**
- `example.com` → Página inicial
- `example.com/pricing` → Planos e preços
- `example.com/features` → Funcionalidades
- `example.com/docs` → Documentação
- `example.com/register` → Cadastro de novos tenants

**Middleware:** `web`

---

## 2. 🏢 Landlord (Administração Central)

**Acesso:** `landlord.example.com` (subdomínio configurável)

**Propósito:** Gerenciamento da aplicação, tenants, configurações globais

**Arquivo de Rotas:** `routes/landlord.php`

**Recursos Disponíveis:**
- `/admin/tenants` → Gerenciamento de tenants
- `/admin/users` → Usuários do sistema
- `/admin/roles` → Funções/Roles globais
- `/admin/permissions` → Permissões globais
- `/admin/settings` → Configurações do sistema
- `/admin/reports` → Relatórios e analytics

**Middleware:** `web`, `auth`, `landlord`

**Permissões:** Requer role `super-admin`

**Configuração:**
```php
// .env
RAPTOR_LANDLORD_SUBDOMAIN=landlord
```

---

## 3. 👥 Tenants (Clientes)

### 3.1 Subdomínios

**Acesso:** `{tenant}.example.com`

**Exemplos:**
- `empresa1.example.com`
- `cliente-xpto.example.com`
- `acme.example.com`

**Arquivo de Rotas:** `routes/tenant.php`

**Recursos Disponíveis:**
- `/` → Página inicial do tenant
- `/dashboard` → Dashboard do tenant
- `/admin/users` → Usuários do tenant
- `/admin/roles` → Roles do tenant
- `/admin/permissions` → Permissões do tenant
- `/admin/settings` → Configurações do tenant

**Middleware:** `web`, `tenant`

**Identificação:** Automática via subdomínio

### 3.2 Domínios Customizados

**Acesso:** Domínio próprio do cliente (ex: `empresaxyz.com.br`)

**Habilitação:**
```php
// .env
RAPTOR_ENABLE_CUSTOM_DOMAINS=true
```

**Middleware:** `web`, `tenant.custom.domain`

**Identificação:** Busca na coluna `custom_domain` da tabela `tenants`

---

## Configuração

### Arquivo: `config/raptor.php`

```php
return [
    // Domínio principal
    'main_domain' => env('RAPTOR_MAIN_DOMAIN', 'localhost'),
    
    // Configuração do Landlord
    'landlord' => [
        'subdomain' => env('RAPTOR_LANDLORD_SUBDOMAIN', 'landlord'),
        'middleware' => ['web', 'auth', 'landlord'],
        'prefix' => 'admin',
    ],
    
    // Configuração dos Tenants
    'tenant' => [
        'middleware' => ['web', 'tenant'],
        'prefix' => 'admin',
        'subdomain_column' => 'subdomain',
        'custom_domain_column' => 'custom_domain',
    ],
    
    // Domínios customizados
    'enable_custom_domains' => env('RAPTOR_ENABLE_CUSTOM_DOMAINS', false),
];
```

---

## Middlewares

### LandlordMiddleware

**Alias:** `landlord`

**Responsabilidades:**
- Verifica autenticação do usuário
- Valida se o usuário possui role `super-admin`
- Define o contexto da aplicação como `landlord`

### TenantMiddleware

**Alias:** `tenant`

**Responsabilidades:**
- Extrai o subdomínio da URL
- Busca o tenant correspondente no banco
- Valida se o tenant está ativo
- Define o tenant atual via Landlord (scoping)
- Define o contexto da aplicação como `tenant`

### TenantCustomDomainMiddleware

**Alias:** `tenant.custom.domain`

**Responsabilidades:**
- Busca tenant pelo domínio customizado
- Valida se o tenant está ativo
- Define o tenant atual via Landlord (scoping)
- Define o contexto da aplicação como `tenant`

---

## Variáveis de Ambiente

```env
# Domínio principal da aplicação
RAPTOR_MAIN_DOMAIN=example.com

# Subdomínio para administração central
RAPTOR_LANDLORD_SUBDOMAIN=landlord

# Habilita domínios customizados para tenants
RAPTOR_ENABLE_CUSTOM_DOMAINS=false

# Estratégia de banco de dados (shared ou separate)
RAPTOR_DB_STRATEGY=shared

# Prefixo para bancos separados (se strategy=separate)
RAPTOR_DB_PREFIX=tenant_

# Disco de armazenamento
RAPTOR_STORAGE_DISK=public
```

---

## Exemplos de Uso

### Definindo Rotas no Landlord

```php
// routes/landlord.php
Route::prefix('admin')->name('landlord.')->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->name('analytics');
});
```

### Definindo Rotas no Tenant

```php
// routes/tenant.php
Route::middleware('auth')->group(function () {
    Route::resource('products', ProductController::class);
});
```

### Acessando o Tenant Atual

```php
// No controller ou view
$tenant = app('current.tenant');
$tenantId = config('app.current_tenant_id');

// Verificando o contexto
if (config('app.context') === 'tenant') {
    // Código específico para tenant
}

if (config('app.context') === 'landlord') {
    // Código específico para landlord
}
```

---

## Fluxo de Requisição

```
Requisição → Roteador → Middleware → Controller → Response

1. Site (example.com)
   → web → SiteController

2. Landlord (landlord.example.com)
   → web → auth → landlord → LandlordController

3. Tenant (cliente.example.com)
   → web → tenant → TenantController

4. Custom Domain (empresaxyz.com.br)
   → web → tenant.custom.domain → TenantController
```

---

## Desenvolvimento Local

### Configuração de Hosts

Para testar localmente, adicione ao `/etc/hosts`:

```
127.0.0.1 example.local
127.0.0.1 landlord.example.local
127.0.0.1 tenant1.example.local
127.0.0.1 tenant2.example.local
```

### .env para Desenvolvimento

```env
RAPTOR_MAIN_DOMAIN=example.local
RAPTOR_LANDLORD_SUBDOMAIN=landlord
RAPTOR_ENABLE_CUSTOM_DOMAINS=false
```

---

## Segurança

- ✅ Isolamento estrito entre tenants via middleware
- ✅ Validação de status do tenant (ativo/inativo)
- ✅ Verificação de permissões no landlord
- ✅ Scoping automático de queries por tenant
- ✅ Prevenção de cross-tenant access

---

## Próximos Passos

1. Implementar model `Tenant` com colunas `subdomain` e `custom_domain`
2. Criar migrations para a tabela `tenants`
3. Implementar controllers específicos
4. Criar views Vue/Inertia para cada contexto
5. Configurar testes para cada contexto
