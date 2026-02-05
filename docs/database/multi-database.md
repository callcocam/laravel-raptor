# Migrations em Múltiplos Bancos de Dados

O comando `raptor:migrate-tenants` executa migrations em bancos de dados separados de tenants, clients e stores.

## Visão Geral

Em cenários de multi-tenancy com bancos separados, cada tenant/client/store pode ter seu próprio banco de dados. Este comando automatiza a execução de migrations em todos eles.

## Funcionalidades

- ✅ Lista automaticamente entidades com campo `database` preenchido
- ✅ Cria o banco de dados automaticamente se não existir
- ✅ Executa migrations específicas por tipo de entidade
- ✅ Evita execução duplicada de migrations já executadas
- ✅ Modo dry-run para simulação
- ✅ Relatório detalhado de resultados

## Configuração

### config/raptor.php

```php
'migrations' => [
    // Migrations executadas em TODOS os bancos
    'default' => [
        '2024_01_01_000000_create_users_table.php',
        '2024_01_02_000000_create_products_table.php',
    ],

    // Migrations específicas para TENANTS
    'tenant' => [
        '2024_01_03_000000_create_tenant_settings_table.php',
    ],

    // Migrations específicas para CLIENTS
    'client' => [
        '2024_01_04_000000_create_client_integrations_table.php',
    ],

    // Migrations específicas para STORES
    'store' => [
        '2024_01_05_000000_create_store_inventory_table.php',
    ],

    'options' => [
        'create_database_if_not_exists' => true,
        'force' => false,
        'dry_run' => false,
        'database_creation_timeout' => 30,
    ],

    'models' => [
        'client' => 'App\\Models\\Client',
        'store' => 'App\\Models\\Store',
    ],
],
```

## Uso do Comando

### Básico

```bash
php artisan raptor:migrate-tenants
```

### Com Opções

```bash
# Forçar sem confirmação
php artisan raptor:migrate-tenants --force

# Apenas simular (dry-run)
php artisan raptor:migrate-tenants --dry-run

# Apenas tenants específicos
php artisan raptor:migrate-tenants --type=tenant

# Apenas clients
php artisan raptor:migrate-tenants --type=client

# Apenas stores
php artisan raptor:migrate-tenants --type=store
```

## Estrutura de Migrations

As migrations devem estar em `database/migrations/`:

```
database/migrations/
├── 2024_01_01_000000_create_users_table.php
├── 2024_01_02_000000_create_products_table.php
├── 2024_01_03_000000_create_tenant_settings_table.php
├── 2024_01_04_000000_create_client_integrations_table.php
└── 2024_01_05_000000_create_store_inventory_table.php
```

## Requisitos para Entidades

Para que uma entidade seja incluída:

1. **Campo `database`** deve estar preenchido
2. **Campo `status`** deve ser `published` (ou configurável)

```php
// Exemplo de Tenant
Tenant::create([
    'name' => 'Acme Corp',
    'domain' => 'acme.example.com',
    'database' => 'tenant_acme', // 👈 Obrigatório
    'status' => 'published',
]);
```

## Criação Automática de Banco

Se `create_database_if_not_exists` for `true`, o comando cria o banco automaticamente:

### MySQL

```sql
CREATE DATABASE IF NOT EXISTS `tenant_acme` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

### PostgreSQL

```sql
CREATE DATABASE tenant_acme;
```

## Saída do Comando

```
Encontrados:
- 5 tenants com database configurado
- 12 clients com database configurado
- 8 stores com database configurado

Total: 25 bancos de dados para migrar

Executando migrations...

[1/25] Tenant: Acme Corp (tenant_acme)
  ✓ 2024_01_01_000000_create_users_table.php
  ✓ 2024_01_02_000000_create_products_table.php
  ✓ 2024_01_03_000000_create_tenant_settings_table.php

[2/25] Client: Loja Central (client_loja_central)
  ✓ 2024_01_01_000000_create_users_table.php
  ✓ 2024_01_02_000000_create_products_table.php
  ✓ 2024_01_04_000000_create_client_integrations_table.php

...

Resumo:
✓ 25 bancos migrados com sucesso
✗ 0 erros
⊘ 0 ignorados
```

## Tratamento de Erros

O comando continua mesmo se um banco falhar:

```
[15/25] Store: Filial Norte (store_filial_norte)
  ✗ Erro de conexão: SQLSTATE[HY000] [2002] Connection refused
  Continuando para próximo banco...

...

Resumo:
✓ 24 bancos migrados com sucesso
✗ 1 erro
⊘ 0 ignorados

Erros:
- store_filial_norte: Connection refused
```
