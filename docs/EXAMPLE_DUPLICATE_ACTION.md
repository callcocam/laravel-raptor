# Exemplo Prático: Adicionando Ação de Duplicar Produto

Este exemplo mostra como implementar uma ação customizada completa no sistema Raptor.

## Objetivo

Adicionar um botão "Duplicar" nas linhas da tabela de produtos que:
- Clone o produto selecionado
- Atualize o nome com " (Cópia)"
- Gere um novo EAN único
- Mostre confirmação antes de executar
- Exiba mensagem de sucesso após duplicar

## Implementação Passo a Passo

### 1. Definir a Rota de Execute

Em `ProductController.php`, na função `getPages()`:

```php
protected function getPages(): array
{
    return [
        'index' => Index::route('/products')
            ->label('Products')
            ->name('products.index')
            ->middlewares(['auth', 'verified']),
            
        // ... outras rotas ...
        
        // Rota para executar ações personalizadas
        'execute' => Execute::route('/products/execute/actions')
            ->label('Executar Product')
            ->name('products.execute')
            ->middlewares(['auth', 'verified']),
    ];
}
```

### 2. Adicionar a Ação na Tabela

Na função `table()` do `ProductController.php`:

```php
protected function table(TableBuilder $table): TableBuilder
{
    // ... configuração de colunas ...
    
    $table->actions([
        // Ações padrão existentes
        ViewAction::make('products.show'),
        EditAction::make('products.edit'),
        
        // 🆕 Nova ação de duplicar
        Action::make('duplicate')
            ->label('Duplicar')
            ->icon('Copy')
            ->color('blue')
            ->variant('outline')
            ->size('sm')
            ->method('POST')
            ->url(fn($record) => route('tenant.products.execute', ['record' => $record->id]))
            ->confirm([
                'title' => 'Duplicar Produto?',
                'message' => 'Deseja criar uma cópia deste produto? O nome será alterado para incluir "(Cópia)".',
                'confirmText' => 'Sim, duplicar',
                'cancelText' => 'Cancelar',
            ])
            ->successMessage('Produto duplicado com sucesso!')
            ->errorMessage('Erro ao duplicar produto.'),
        
        // Outras ações
        DeleteAction::make('products.destroy'),
    ]);
    
    return $table;
}
```

### 3. Implementar o Método Execute

Ainda no `ProductController.php`:

```php
use Illuminate\Http\Request;

/**
 * Executa ações personalizadas em produtos.
 */
public function execute(Request $request): \Illuminate\Http\RedirectResponse
{
    // Valida os dados recebidos
    $validated = $request->validate([
        'action' => 'required|string',
        'record_id' => 'required|integer|exists:products,id',
    ]);

    // Busca o produto
    $product = Product::findOrFail($validated['record_id']);
    $actionName = $validated['action'];

    // Executa a ação específica usando match (PHP 8+)
    match ($actionName) {
        'duplicate' => $this->duplicateProduct($product),
        'export' => $this->exportProduct($product),
        default => abort(400, "Ação não reconhecida: {$actionName}")
    };

    return back()->with('success', 'Ação executada com sucesso!');
}

/**
 * Duplica um produto existente.
 */
protected function duplicateProduct(Product $product): void
{
    // Replica o produto (copia todos os atributos exceto chave primária)
    $newProduct = $product->replicate();
    
    // Atualiza campos específicos
    $newProduct->name = $product->name . ' (Cópia)';
    $newProduct->ean = $product->ean . '-COPY-' . now()->timestamp;
    
    // Salva o novo produto
    $newProduct->save();
    
    // Se houver relacionamentos, pode duplicá-los aqui
    // Exemplo: copiar imagens, categorias, etc.
}
```

### 4. Adicionar o Import Necessário

No topo do `ProductController.php`:

```php
use Illuminate\Http\Request;
```

## O Que Acontece no Frontend

Quando o usuário clica no botão "Duplicar":

### 1. ActionButton.vue Processa o Clique

```typescript
// Valida se há URL
if (!props.action.url) return;

// Prepara os dados
const formData = {
  record_id: props.record.id,  // ID do produto a duplicar
  action: 'duplicate'           // Nome da ação
};

// Configura a requisição Inertia
const actionConfig = {
  url: '/tenant/products/execute/actions',
  method: 'POST',
  inertia: {
    preserveScroll: true,
    preserveState: false
  }
};

// Executa via Inertia.js router
await execute(actionConfig, formData);
```

### 2. Modal de Confirmação Aparece

```
┌─────────────────────────────────────┐
│   Duplicar Produto?                 │
│                                     │
│   Deseja criar uma cópia deste      │
│   produto? O nome será alterado     │
│   para incluir "(Cópia)".           │
│                                     │
│   [ Cancelar ]  [ Sim, duplicar ]  │
└─────────────────────────────────────┘
```

### 3. Após Confirmação

```typescript
// POST /tenant/products/execute/actions
// Body: { action: 'duplicate', record_id: 123 }

// Backend processa...

// Retorna com mensagem de sucesso
back()->with('success', 'Ação executada com sucesso!')

// Frontend mostra notificação
🟢 Produto duplicado com sucesso!
```

## Fluxo Completo

```
Usuário clica "Duplicar"
        ↓
ActionButton.vue valida dados
        ↓
Modal de confirmação abre
        ↓
Usuário confirma
        ↓
POST /tenant/products/execute/actions
        ↓
ProductController::execute() valida
        ↓
Match identifica ação 'duplicate'
        ↓
duplicateProduct() executa
        ↓
Novo produto criado no banco
        ↓
Redirect back() com mensagem
        ↓
Tabela recarrega
        ↓
Notificação de sucesso aparece
```

## Variações Comuns

### Ação Sem Confirmação

```php
Action::make('export')
    ->label('Exportar')
    ->icon('Download')
    ->color('green')
    ->method('GET')
    ->targetBlank(); // Abre em nova aba
```

### Ação com Modal de Formulário

```php
Action::make('quick-edit')
    ->label('Edição Rápida')
    ->icon('Edit')
    ->actionType('modal')
    ->modalSize('lg')
    ->columns([
        TextField::make('name')
            ->label('Nome')
            ->required(),
        TextField::make('price')
            ->label('Preço')
            ->required(),
    ]);
```

### Ação em Lote (Bulk Action)

```php
$table->bulkActions([
    Action::make('delete-selected')
        ->label('Excluir Selecionados')
        ->icon('Trash2')
        ->color('red')
        ->confirm([
            'title' => 'Excluir produtos?',
            'message' => 'Esta ação não pode ser desfeita.',
        ]),
]);
```

## Testando a Implementação

### 1. Verificar Rota

```bash
php artisan route:list | grep products.execute
```

Deve exibir:
```
POST   tenant/products/execute/actions ... products.execute
```

### 2. Testar no Frontend

1. Acesse a listagem de produtos
2. Localize um produto na tabela
3. Clique no botão "Duplicar" (ícone de cópia)
4. Confirme a ação
5. Verifique se o novo produto aparece na lista

### 3. Verificar no Banco

```bash
php artisan tinker
```

```php
// Ver produtos duplicados
Product::where('name', 'LIKE', '%(Cópia)%')->get();

// Ver EANs com COPY
Product::where('ean', 'LIKE', '%-COPY-%')->get();
```

## Autorização

Para adicionar controle de acesso:

```php
Action::make('duplicate')
    ->label('Duplicar')
    // ... outras configurações ...
    ->visible(fn($record) => auth()->user()->can('duplicate', $record))
    ->authorize(fn($record) => auth()->user()->can('duplicate', $record));
```

Defina a policy:

```php
// app/Policies/ProductPolicy.php
public function duplicate(User $user, Product $product): bool
{
    return $user->hasPermission('products.duplicate');
}
```

## Debug

Se a ação não funcionar:

### Verificar Console do Browser

```javascript
// Deve aparecer:
Action URL: /tenant/products/execute/actions
Action Method: POST
```

### Adicionar Log no Controller

```php
public function execute(Request $request): \Illuminate\Http\RedirectResponse
{
    \Log::info('Execute action', [
        'action' => $request->action,
        'record_id' => $request->record_id,
        'user_id' => auth()->id(),
    ]);
    
    // ... resto do código
}
```

### Verificar Network Tab

```
Request URL: /tenant/products/execute/actions
Request Method: POST
Status Code: 302 (redirect)

Form Data:
  action: duplicate
  record_id: 123
```

## Próximos Passos

1. ✅ Adicionar mais ações (exportar, enviar notificação, etc.)
2. ✅ Implementar ações em lote
3. ✅ Adicionar autorização por policies
4. ✅ Criar testes automatizados
5. ✅ Adicionar tratamento de erros específicos

## Recursos Adicionais

- [Guia Completo de Ações](./ACTIONS_GUIDE.md)
- [Documentação do Action.php](../src/Support/Actions/Action.php)
- [Exemplos de Actions Types](../src/Support/Actions/Types/)
