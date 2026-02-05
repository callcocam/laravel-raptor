# Novas Colunas para InfoList

O Raptor inclui colunas especiais para exibição de dados em InfoList/Show pages.

## LinkColumn

Renderiza um link clicável (usando `<a>` ou `<Link>` do Inertia).

### Link Externo

```php
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\LinkColumn;

LinkColumn::make('website', 'Site')
    ->url(fn($row) => $row->website_url)
    ->openInNewTab()
    ->external();  // Usa <a> ao invés de <Link>
```

### Link Interno (Inertia)

```php
LinkColumn::make('details', 'Ver Detalhes')
    ->url(fn($row) => route('products.show', $row))
    ->icon('Eye');
```

### Com Ícone e Estilo

```php
LinkColumn::make('download', 'Download')
    ->url(fn($row) => $row->file_url)
    ->icon('Download')
    ->color('blue')
    ->openInNewTab();
```

## HasManyColumn

Exibe uma lista de itens de um relacionamento hasMany.

### Básico

```php
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\HasManyColumn;

HasManyColumn::make('orders', 'Pedidos')
    ->relationship('orders')
    ->displayField('number')
    ->limit(5);
```

### Com Ações

```php
use Callcocam\LaravelRaptor\Support\Actions\Types\EditAction;
use Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction;

HasManyColumn::make('orders', 'Pedidos')
    ->relationship('orders')
    ->displayField('number')
    ->limit(5)
    ->actions([
        ViewAction::make('view')
            ->url(fn($record) => route('orders.show', $record)),
        EditAction::make('edit')
            ->url(fn($record) => route('orders.edit', $record)),
    ]);
```

### Com Campos Múltiplos

```php
HasManyColumn::make('order_items', 'Itens do Pedido')
    ->relationship('items')
    ->displayFields(['product.name', 'quantity', 'price'])
    ->separator(' - ')
    ->limit(10);
```

## BelongsToManyColumn

Renderiza relacionamentos many-to-many como badges/tags.

### Básico

```php
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\BelongsToManyColumn;

BelongsToManyColumn::make('categories', 'Categorias')
    ->relationship('categories')
    ->displayField('name');
```

### Com Ações

```php
BelongsToManyColumn::make('categories', 'Categorias')
    ->relationship('categories')
    ->displayField('name')
    ->limit(10)
    ->actions([
        ViewAction::make('view')
            ->url(fn($record) => route('categories.show', $record)),
    ]);
```

### Tags Simples (Sem Ações)

```php
BelongsToManyColumn::make('tags', 'Tags')
    ->relationship('tags')
    ->displayField('name')
    ->badgeColor('blue');
```

## Exemplo Completo

```php
protected function infoList(InfoListBuilder $infoList): InfoListBuilder
{
    return $infoList
        ->columns([
            TextColumn::make('name', 'Nome'),
            TextColumn::make('email', 'E-mail'),
            
            LinkColumn::make('website', 'Website')
                ->url(fn($row) => $row->website)
                ->external()
                ->openInNewTab()
                ->icon('ExternalLink'),
            
            HasManyColumn::make('orders', 'Últimos Pedidos')
                ->relationship('orders')
                ->displayField('number')
                ->limit(5)
                ->orderBy('created_at', 'desc')
                ->actions([
                    ViewAction::make('view')
                        ->icon('Eye')
                        ->url(fn($record) => route('orders.show', $record)),
                ]),
            
            BelongsToManyColumn::make('roles', 'Papéis')
                ->relationship('roles')
                ->displayField('name')
                ->badgeColor('purple'),
            
            BelongsToManyColumn::make('permissions', 'Permissões')
                ->relationship('permissions')
                ->displayField('name')
                ->limit(10)
                ->badgeColor('gray'),
        ]);
}
```

## Configurações Comuns

### displayField

Campo a ser exibido de cada item:

```php
->displayField('name')
->displayField('title')
->displayField('number')
```

### displayFields (Múltiplos)

Exibe múltiplos campos concatenados:

```php
->displayFields(['name', 'email'])
->separator(' - ')
```

### limit

Limita quantidade de itens exibidos:

```php
->limit(5)
->limit(10)
```

### orderBy

Ordena os itens:

```php
->orderBy('created_at', 'desc')
->orderBy('name', 'asc')
```

### badgeColor

Cor dos badges (BelongsToManyColumn):

```php
->badgeColor('blue')    // blue, green, red, yellow, purple, gray
```

### emptyState

Mensagem quando não há itens:

```php
->emptyState('Nenhum item encontrado')
```
