# Novas Colunas para InfoList

## LinkColumn

Renderiza um link clicável (usando `<a>` ou `<Link>` do Inertia).

```php
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\LinkColumn;

LinkColumn::make('website', 'Site')
    ->url(fn($row) => $row->website_url)
    ->openInNewTab()
    ->external(), // Usa <a> ao invés de <Link>

// Ou para links internos (Inertia)
LinkColumn::make('details', 'Ver Detalhes')
    ->url(fn($row) => route('products.show', $row))
    ->icon('Eye'),
```

## HasManyColumn

Exibe uma lista de itens relacionados com ações opcionais.

```php
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\HasManyColumn;
use Callcocam\LaravelRaptor\Support\Actions\Types\EditAction;
use Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction;

HasManyColumn::make('orders', 'Pedidos')
    ->relationship('orders')
    ->displayField('number') // Campo a ser exibido
    ->limit(5) // Máximo de itens
    ->actions([
        ViewAction::make('view')
            ->url(fn($record) => route('orders.show', $record)),
        EditAction::make('edit')
            ->url(fn($record) => route('orders.edit', $record)),
    ]),
```

## BelongsToManyColumn

Similar ao HasManyColumn, mas renderiza os itens como badges/tags.

```php
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\BelongsToManyColumn;

BelongsToManyColumn::make('categories', 'Categorias')
    ->relationship('categories')
    ->displayField('name')
    ->limit(10)
    ->actions([
        ViewAction::make('view')
            ->url(fn($record) => route('categories.show', $record)),
    ]),

// Ou para tags simples sem ações
BelongsToManyColumn::make('tags', 'Tags')
    ->displayField('name'),
```

## Componentes Vue Criados

- `InfolistLink.vue` - Renderiza links com suporte a Inertia ou externos
- `InfolistHasMany.vue` - Lista vertical de itens com ações
- `InfolistBelongsToMany.vue` - Badges/tags horizontais com ações

## Registro Automático

Os componentes são registrados automaticamente em:
- `packages/callcocam/laravel-raptor/resources/js/raptor/index.ts`

Não é necessário registrá-los manualmente no projeto.
