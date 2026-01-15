# Laravel Raptor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/callcocam/laravel-raptor.svg?style=flat-square)](https://packagist.org/packages/callcocam/laravel-raptor)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/callcocam/laravel-raptor/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/callcocam/laravel-raptor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/callcocam/laravel-raptor/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/callcocam/laravel-raptor/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/callcocam/laravel-raptor.svg?style=flat-square)](https://packagist.org/packages/callcocam/laravel-raptor)

Um pacote completo Laravel para construção rápida de aplicações multi-tenant com sistema de permissões integrado, formulários dinâmicos e tabelas interativas usando Inertia.js e shadcn-vue.

## Características Principais

- **Multi-Tenancy Completo**: Suporte para subdomínios e domínios customizados
- **Sistema Shinobi**: Gerenciamento robusto de roles e permissões
- **Form Fields Modernos**: Componentes Vue 3 usando primitivos shadcn-vue
- **Table Columns Personalizadas**: 9+ tipos de colunas pré-configuradas
- **Controllers Inteligentes**: Sistema de controllers com métodos padronizados
- **Navegação Automática**: Geração automática de menus baseada em controllers
- **Inertia.js Integrado**: SSR-ready com TypeScript

## Requisitos

- PHP 8.2+
- Laravel 12.0+
- Node.js 18+
- Inertia.js 2.0+

## Instalação

Instale o pacote via Composer:

```bash
composer require callcocam/laravel-raptor
```

Publique os arquivos de configuração:

```bash
php artisan vendor:publish --tag="raptor-config"
```

Publique as migrations:

```bash
php artisan vendor:publish --tag="raptor-migrations"
```

Execute as migrations:

```bash
php artisan migrate
```

## Configuração Rápida

### 1. Configure o arquivo `.env`

```env
RAPTOR_MAIN_DOMAIN=localhost
RAPTOR_LANDLORD_SUBDOMAIN=landlord
RAPTOR_DB_STRATEGY=shared
```

### 2. Adicione o trait HasRolesAndPermissions ao seu User Model

```php
use Callcocam\LaravelRaptor\Support\Shinobi\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
}
```

### 3. Configure seus controllers

```php
use Callcocam\LaravelRaptor\Http\Controllers\TenantController;

class ProductController extends TenantController
{
    public function model(): ?string
    {
        return Product::class;
    }

    public function getPages(): array
    {
        return [
            'index' => Index::route('/products')
                ->label('Produtos')
                ->icon('Package'),
        ];
    }

    protected function form(Form $form): Form
    {
        return $form->columns([
            TextField::make('name', 'Nome')->required(),
            TextareaField::make('description', 'Descrição'),
        ]);
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        return $table->columns([
            TextColumn::make('name', 'Nome')->searchable(),
            DateColumn::make('created_at', 'Criado em')->relative(),
        ]);
    }
}
```

## Documentação Completa

Acesse a documentação completa em `/docs`:

- [Form Fields](docs/form-fields.md) - Componentes de formulário
- [Table Columns](docs/table-columns.md) - Colunas de tabela personalizadas
- [Controllers](docs/controllers.md) - Estrutura de controllers
- [Shinobi](docs/shinobi.md) - Sistema de roles e permissões
- [Multi-Tenancy](docs/multi-tenancy.md) - Configuração de multi-tenancy
- [Migration Guide](docs/migration-guide.md) - Migração de FormColumn para FormField
- [Examples](docs/examples.md) - Exemplos práticos

## Início Rápido

### Criar um CRUD Completo

```php
// 1. Crie seu Model
php artisan make:model Product -m

// 2. Crie o Controller
php artisan make:controller Tenant/ProductController

// 3. Estenda TenantController e implemente os métodos
class ProductController extends TenantController
{
    public function model(): ?string
    {
        return Product::class;
    }

    // Implemente: getPages(), form(), table()
}

// 4. As rotas são registradas automaticamente!
```

## Form Fields Disponíveis

- `FormFieldText` - Input de texto com prepend/append
- `FormFieldTextarea` - Área de texto com contador
- `FormFieldEmail` - Campo de email validado
- `FormFieldPassword` - Senha com toggle de visibilidade
- `FormFieldNumber` - Input numérico
- `FormFieldDate` - Seletor de data/datetime
- `FormFieldSelect` - Select com normalização
- `FormFieldCheckbox` - Checkbox com layouts flexíveis
- `FormFieldFileUpload` - Upload com drag & drop
- `FormFieldHidden` - Campo oculto

## Table Columns Disponíveis

- `TextColumn` - Texto simples
- `EmailColumn` - Email formatado
- `DateColumn` - Data com formatos customizáveis
- `BooleanColumn` - Sim/Não com cores
- `PhoneColumn` - Telefone com formatação BR
- `StatusColumn` - Status com badges coloridos
- `MoneyColumn` - Valores monetários (BRL, USD, EUR)
- `ImageColumn` - Exibição de imagens
- `BadgeColumn` - Badges personalizados

## Multi-Tenancy

O Laravel Raptor suporta dois modos de multi-tenancy:

### Shared Database (Padrão)
```env
RAPTOR_DB_STRATEGY=shared
```

Todos os tenants compartilham o mesmo banco de dados. Os registros são isolados pela coluna `tenant_id`.

### Separate Database
```env
RAPTOR_DB_STRATEGY=separate
RAPTOR_DB_PREFIX=tenant_
```

Cada tenant possui seu próprio banco de dados (ex: `tenant_1`, `tenant_2`).

## Sistema Shinobi (Roles & Permissions)

```php
// Criar uma role
$role = Role::create(['name' => 'admin', 'slug' => 'admin']);

// Criar uma permissão
$permission = Permission::create([
    'name' => 'Criar Produtos',
    'slug' => 'products.create'
]);

// Atribuir permissão à role
$role->permissions()->attach($permission);

// Atribuir role ao usuário
$user->roles()->attach($role);

// Verificar permissões
if ($user->hasPermission('products.create')) {
    // usuário pode criar produtos
}
```

## Exemplo de Uso

### Formulário Completo

```php
protected function form(Form $form): Form
{
    return $form->columns([
        TextField::make('name', 'Nome do Produto')
            ->required()
            ->placeholder('Digite o nome'),

        SelectField::make('category_id', 'Categoria')
            ->options(Category::pluck('name', 'id'))
            ->searchable(),

        TextareaField::make('description', 'Descrição')
            ->maxLength(500)
            ->rows(5),

        CheckboxField::make('active', 'Ativo')
            ->default(true),
    ]);
}
```

### Tabela com Filtros

```php
protected function table(TableBuilder $table): TableBuilder
{
    return $table
        ->columns([
            TextColumn::make('name', 'Nome')->searchable(),
            StatusColumn::make('status', 'Status')
                ->statuses([
                    'active' => ['label' => 'Ativo', 'color' => 'success'],
                    'inactive' => ['label' => 'Inativo', 'color' => 'danger'],
                ]),
            DateColumn::make('created_at', 'Criado')->relative(),
        ])
        ->actions([
            EditAction::make('products.edit'),
            DeleteAction::make('products.destroy'),
        ]);
}
```

## Migrations em Múltiplos Bancos

O Raptor inclui um comando para executar migrations em múltiplos bancos de dados (tenants, clients e stores):

```bash
php artisan raptor:migrate-tenants
```

Este comando:
- Lista automaticamente todos os tenants/clients/stores com banco de dados configurado
- Cria o banco de dados automaticamente se não existir
- Executa migrations específicas conforme configuração
- Suporta filtros por tipo e database

**Documentação completa**: Veja [MULTI_DATABASE_MIGRATIONS.md](docs/MULTI_DATABASE_MIGRATIONS.md) para detalhes.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Claudio Campos](https://github.com/callcocam)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
