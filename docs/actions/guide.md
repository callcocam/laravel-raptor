# Guia de Actions

As ações no Raptor permitem criar botões interativos com diferentes comportamentos: links, callbacks, confirmações, modais, etc.

## Tipos de Ações

### Action (Genérico)

```php
use Callcocam\LaravelRaptor\Support\Actions\Action;

Action::make('duplicate')
    ->label('Duplicar')
    ->icon('Copy')
    ->color('blue')
    ->method('POST')
    ->url(fn($record) => route('products.duplicate', $record))
    ->confirm([
        'title' => 'Duplicar Produto?',
        'message' => 'Deseja realmente duplicar este produto?',
    ]);
```

### ViewAction

```php
use Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction;

ViewAction::make('products.show')
    ->icon('Eye')
    ->label('Visualizar');
```

### EditAction

```php
use Callcocam\LaravelRaptor\Support\Actions\Types\EditAction;

EditAction::make('products.edit')
    ->icon('Pencil')
    ->label('Editar');
```

### DeleteAction

```php
use Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction;

DeleteAction::make('products.destroy')
    ->confirm([
        'title' => 'Excluir Produto?',
        'message' => 'Esta ação não pode ser desfeita.',
        'confirmText' => 'Sim, excluir',
        'cancelText' => 'Cancelar',
    ]);
```

## Configurações Comuns

### Cores

```php
Action::make('action')
    ->color('blue')    // default, blue, green, yellow, red, gray
```

### Variantes

```php
Action::make('action')
    ->variant('outline')  // default, outline, destructive, secondary, ghost
```

### Tamanhos

```php
Action::make('action')
    ->size('sm')  // sm, default, lg, icon
```

### Ícones

Use nomes de ícones do Lucide Icons:

```php
Action::make('action')
    ->icon('Plus')        // Adicionar
    ->icon('Trash')       // Excluir
    ->icon('Download')    // Download
    ->icon('Copy')        // Duplicar
    ->icon('Eye')         // Visualizar
    ->icon('Pencil')      // Editar
```

### URLs

```php
// URL estática
Action::make('action')->url('/products');

// URL dinâmica com record
Action::make('action')->url(fn($record) => route('products.show', $record));

// URL com named route
Action::make('products.show');  // Usa o nome como route name
```

### Método HTTP

```php
Action::make('action')
    ->method('POST')   // GET, POST, PUT, PATCH, DELETE
```

### Confirmação

```php
Action::make('action')
    ->confirm([
        'title' => 'Confirmar Ação?',
        'message' => 'Tem certeza que deseja continuar?',
        'confirmText' => 'Confirmar',
        'cancelText' => 'Cancelar',
    ]);
```

### Visibilidade Condicional

```php
Action::make('action')
    ->visible(fn($record) => $record->status === 'draft')
    ->hidden(fn($record) => $record->is_protected);
```

### Desabilitar

```php
Action::make('action')
    ->disabled(fn($record) => $record->is_locked);
```

## Usar na Tabela

```php
protected function table(TableBuilder $table): TableBuilder
{
    $table->actions([
        ViewAction::make('products.show'),
        EditAction::make('products.edit'),
        
        Action::make('duplicate')
            ->label('Duplicar')
            ->icon('Copy')
            ->color('blue')
            ->method('POST')
            ->url(fn($record) => route('products.execute'))
            ->data(fn($record) => [
                'action' => 'duplicate',
                'record_id' => $record->id,
            ]),
            
        DeleteAction::make('products.destroy'),
    ]);
    
    return $table;
}
```

## Implementar Rota de Execute

```php
// ProductController.php
public function execute(Request $request)
{
    $validated = $request->validate([
        'action' => 'required|string',
        'record_id' => 'required|exists:products,id',
    ]);

    $product = Product::findOrFail($validated['record_id']);

    match ($validated['action']) {
        'duplicate' => $this->duplicateProduct($product),
        'archive' => $this->archiveProduct($product),
        default => abort(400, "Ação não reconhecida")
    };

    return back()->with('success', 'Ação executada com sucesso!');
}

protected function duplicateProduct(Product $product): void
{
    $newProduct = $product->replicate();
    $newProduct->name = $product->name . ' (Cópia)';
    $newProduct->sku = $product->sku . '-copy-' . time();
    $newProduct->save();
}
```

## Ações em Header

```php
protected function getHeaderActions(): array
{
    return [
        Action::make('create')
            ->label('Novo Produto')
            ->icon('Plus')
            ->url(route('products.create')),
            
        Action::make('export')
            ->label('Exportar')
            ->icon('Download')
            ->method('POST')
            ->url(route('products.export')),
    ];
}
```

## Ações em Formulário

```php
protected function getFormActions(): array
{
    return [
        Action::make('save')
            ->label('Salvar')
            ->type('submit')
            ->color('green'),
            
        Action::make('cancel')
            ->label('Cancelar')
            ->url(route('products.index'))
            ->color('gray'),
    ];
}
```
