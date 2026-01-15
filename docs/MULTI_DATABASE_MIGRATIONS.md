# Migrations em Múltiplos Bancos de Dados

Este documento explica como usar o comando `raptor:migrate-tenants` para executar migrations em múltiplos bancos de dados de tenants, clients e stores.

## Visão Geral

O comando `raptor:migrate-tenants` permite executar migrations em todos os bancos de dados configurados para tenants, clients e stores que possuem o campo `database` preenchido. Isso é útil em cenários de multi-tenancy onde cada tenant/cliente/loja possui seu próprio banco de dados.

## Funcionalidades

- ✅ Lista automaticamente todos os tenants, clients e stores com banco de dados configurado
- ✅ Cria o banco de dados automaticamente se não existir (MySQL e PostgreSQL)
- ✅ Executa migrations específicas conforme configuração
- ✅ Evita execução duplicada de migrations já executadas
- ✅ Modo dry-run para simulação sem alterações
- ✅ Relatório detalhado de sucessos, erros e ignorados

## Configuração

### 1. Configurar Migrations

Edite o arquivo `config/raptor.php` e configure as migrations que devem ser executadas:

```php
'migrations' => [
    // Migrations executadas em TODOS os bancos (tenant, client, store)
    'default' => [
        '2024_01_01_000000_create_users_table.php',
        '2024_01_02_000000_create_products_table.php',
    ],

    // Migrations específicas para bancos de TENANTS
    'tenant' => [
        '2024_01_03_000000_create_tenant_settings_table.php',
    ],

    // Migrations específicas para bancos de CLIENTS
    'client' => [
        '2024_01_04_000000_create_client_integrations_table.php',
    ],

    // Migrations específicas para bancos de STORES
    'store' => [
        '2024_01_05_000000_create_store_inventory_table.php',
    ],

    // Configurações adicionais
    'options' => [
        // Cria banco automaticamente se não existir
        'create_database_if_not_exists' => true,
        
        // Força execução mesmo se já foi executada
        'force' => false,
        
        // Modo dry-run (apenas simulação)
        'dry_run' => false,
        
        // Timeout para criação de banco (segundos)
        'database_creation_timeout' => 30,
    ],

    // Models customizados (opcional)
    'models' => [
        'client' => 'App\\Models\\Client',
        'store' => 'App\\Models\\Store',
    ],
],
```

### 2. Estrutura de Migrations

As migrations devem estar no diretório padrão do Laravel:
```
database/migrations/
```

O comando busca os arquivos pelo nome exato especificado na configuração.

## Uso do Comando

### Comando Básico

Executa migrations em todos os bancos encontrados:

```bash
php artisan raptor:migrate-tenants
```

O comando irá:
1. Listar todos os tenants/clients/stores com `database` preenchido
2. Pedir confirmação (a menos que use `--force`)
3. Executar migrations em cada banco

### Opções Disponíveis

#### `--force`
Força a execução sem pedir confirmação:

```bash
php artisan raptor:migrate-tenants --force
```

#### `--dry-run`
Executa em modo simulação, mostrando o que seria feito sem fazer alterações:

```bash
php artisan raptor:migrate-tenants --dry-run
```

#### `--type=TIPO`
Filtra por tipo específico. Valores: `tenant`, `client`, `store`:

```bash
# Apenas tenants
php artisan raptor:migrate-tenants --type=tenant

# Apenas clients
php artisan raptor:migrate-tenants --type=client

# Apenas stores
php artisan raptor:migrate-tenants --type=store
```

#### `--database=NOME_DB`
Executa apenas no banco de dados específico:

```bash
php artisan raptor:migrate-tenants --database=tenant_123
```

### Exemplos de Uso

#### 1. Executar em todos os bancos (com confirmação)
```bash
php artisan raptor:migrate-tenants
```

#### 2. Executar apenas em tenants (sem confirmação)
```bash
php artisan raptor:migrate-tenants --type=tenant --force
```

#### 3. Simular execução em um banco específico
```bash
php artisan raptor:migrate-tenants --database=meu_banco --dry-run
```

#### 4. Executar em clients e stores
```bash
php artisan raptor:migrate-tenants --type=client --force
php artisan raptor:migrate-tenants --type=store --force
```

## Fluxo de Execução

1. **Busca de Registros**: O comando busca todos os tenants/clients/stores com campo `database` preenchido
2. **Listagem**: Exibe uma tabela com os registros encontrados
3. **Confirmação**: Solicita confirmação (a menos que use `--force`)
4. **Para cada banco**:
   - Cria conexão temporária
   - Verifica se o banco existe
   - Cria o banco se não existir (se configurado)
   - Executa migrations configuradas
   - Registra migrations executadas
   - Remove conexão temporária
5. **Relatório Final**: Exibe estatísticas de sucessos, erros e ignorados

## Requisitos dos Modelos

### Tenant
O modelo `Tenant` já possui o campo `database` por padrão. Nenhuma configuração adicional é necessária.

### Client e Store
Para que o comando funcione com Clients e Stores, eles precisam:

1. **Ter o campo `database` na tabela**:
   ```php
   // Migration
   $table->string('database')->nullable();
   ```

2. **Ter o campo no fillable do modelo** (opcional, mas recomendado):
   ```php
   protected $fillable = [
       // ... outros campos
       'database',
   ];
   ```

3. **Configurar o modelo na config** (se usar namespace diferente):
   ```php
   'migrations' => [
       'models' => [
           'client' => 'App\\Models\\Client',
           'store' => 'App\\Models\\Store',
       ],
   ],
   ```

## Tratamento de Erros

O comando trata os seguintes cenários:

- ✅ **Banco não existe**: Cria automaticamente (se configurado)
- ✅ **Migration já executada**: Ignora (a menos que use `--force`)
- ✅ **Migration não encontrada**: Avisa e continua
- ✅ **Erro na execução**: Exibe erro e continua com próximo banco
- ✅ **Conexão falha**: Remove conexão temporária e continua

## Logs e Saída

O comando exibe informações detalhadas durante a execução:

```
🚀 Iniciando execução de migrations em múltiplos bancos...

📊 Encontrados 3 registro(s) com banco de dados configurado:

+--------+------------------+------------------+------------------+
| Tipo   | Nome             | Database         | ID               |
+--------+------------------+------------------+------------------+
| Tenant | Empresa ABC      | tenant_abc       | 01ABC...         |
| Client | Cliente XYZ      | client_xyz       | 01XYZ...         |
| Store  | Loja 123         | store_123        | 01123...         |
+--------+------------------+------------------+------------------+

📦 Processando Tenant: Empresa ABC (DB: tenant_abc)
   ✅ Banco de dados criado com sucesso!
   🔄 Executando 2 migration(s)...
   🔄 Executando: 2024_01_01_000000_create_users_table.php
   ✅ Migration executada: 2024_01_01_000000_create_users_table.php
   ...

✅ Execução concluída!

+-----------+------------+
| Status    | Quantidade |
+-----------+------------+
| ✅ Sucesso| 3          |
| ❌ Erro   | 0          |
| ⏭️  Ignorados | 0      |
+-----------+------------+
```

## Boas Práticas

1. **Sempre teste em dry-run primeiro**:
   ```bash
   php artisan raptor:migrate-tenants --dry-run
   ```

2. **Use `--force` apenas em produção** quando tiver certeza:
   ```bash
   php artisan raptor:migrate-tenants --force
   ```

3. **Execute por tipo** quando quiser controlar melhor:
   ```bash
   php artisan raptor:migrate-tenants --type=tenant --force
   ```

4. **Mantenha migrations organizadas** por tipo na configuração

5. **Use migrations idempotentes** que podem ser executadas múltiplas vezes sem problemas

## Troubleshooting

### Erro: "Nenhum registro encontrado"
- Verifique se os registros têm o campo `database` preenchido
- Verifique se o modelo está configurado corretamente
- Use `--type` para filtrar por tipo específico

### Erro: "Migration não encontrada"
- Verifique se o nome do arquivo está correto na config
- Verifique se o arquivo existe em `database/migrations/`
- O nome deve ser exato, incluindo a extensão `.php`

### Erro: "Banco de dados não existe"
- O comando tenta criar automaticamente
- Verifique permissões do usuário do banco
- Verifique se o driver suporta criação automática (MySQL/PostgreSQL)

### Erro: "Classe não encontrada"
- Verifique se o nome da classe na migration está correto
- O comando tenta inferir o nome da classe do arquivo
- Se necessário, ajuste o método `getMigrationClassName()`

## Suporte a Drivers de Banco

Atualmente suporta:
- ✅ **MySQL/MariaDB**: Criação automática de banco
- ✅ **PostgreSQL**: Criação automática de banco
- ⚠️ **SQLite**: Não suporta criação automática (use arquivo pré-existente)
- ⚠️ **SQL Server**: Não testado (pode funcionar)

## Integração com CI/CD

O comando pode ser usado em pipelines de CI/CD:

```yaml
# Exemplo GitHub Actions
- name: Run migrations on all tenant databases
  run: php artisan raptor:migrate-tenants --force
```

## Segurança

- ⚠️ O comando cria bancos de dados automaticamente - use com cuidado
- ⚠️ O comando executa migrations que podem modificar dados - sempre faça backup
- ✅ O comando verifica se migrations já foram executadas (evita duplicatas)
- ✅ Use `--dry-run` para validar antes de executar

## Changelog

### v1.0.0
- Criação inicial do comando
- Suporte a tenants, clients e stores
- Criação automática de banco de dados
- Modo dry-run
- Filtros por tipo e database

