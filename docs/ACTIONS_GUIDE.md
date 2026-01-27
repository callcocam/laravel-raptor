# Guia de Ações (Actions)

Este guia explica como criar e executar ações no sistema Raptor usando o padrão `Action.php`.

## Visão Geral

As ações no Raptor seguem um padrão consistente que integra:
- Backend: `Action.php` (gera URLs e configurações)
- Frontend: `ActionButton.vue` (executa via Inertia.js)
- Rotas: Rota padrão `/execute` ou rotas personalizadas

## Tipos de Ações

### 1. Action com Rota Padrão (`/execute`)

Usa a rota genérica do sistema para executar ações simples.

**Backend (Action.php):**
```php
use Callcocam\LaravelRaptor\Support\Actions\Action;

Action::make('duplicate')
    ->label('Duplicar')
    ->icon('Copy')
    ->color('blue')
    ->method('POST')
    // URL será gerada automaticamente: /tenant/execute
    ->confirm([
        'title' => 'Duplicar Produto?',
        'message' => 'Deseja realmente duplicar este produto?',
    ]);
```

**Controller (`ExecuteController.php`):**
```php
public function execute(Request $request)
{
    $validated = $request->validate([
        'action' => 'required|string',
        'record_id' => 'required|integer',
    ]);

    // Processar ação genérica
    return back()->with('success', 'Ação executada!');
}
```

### 2. Action com Rota Personalizada

Para ações específicas de um recurso, defina uma rota personalizada.

**Controller (`ProductController.php`):**

1. **Definir a página Execute em `getPages()`:**
```php
protected function getPages(): array
{
    return [
        // ... outras páginas
        'execute' => Execute::route('/products/execute/actions')
            ->label('Executar Product')
            ->name('products.execute')
            ->middlewares(['auth', 'verified']),
    ];
}
```

2. **Implementar o método execute:**
```php
public function execute(Request $request)
{
    $validated = $request->validate([
        'action' => 'required|string',
        'record_id' => 'required|integer|exists:products,id',
    ]);

    $product = Product::findOrFail($validated['record_id']);
    $actionName = $validated['action'];

    // Executa a ação específica usando match
    match ($actionName) {
        'duplicate' => $this->duplicateProduct($product),
        'export' => $this->exportProduct($product),
        'send_notification' => $this->sendNotification($product),
        default => abort(400, "Ação não reconhecida: {$actionName}")
    };

    return back()->with('success', 'Ação executada com sucesso!');
}

protected function duplicateProduct(Product $product): void
{
    $newProduct = $product->replicate();
    $newProduct->name = $product->name . ' (Cópia)';
    $newProduct->save();
}
```

3. **Definir a ação na tabela:**
```php
protected function table(TableBuilder $table): TableBuilder
{
    $table->actions([
        // Ação personalizada de duplicar
        Action::make('duplicate')
            ->label('Duplicar')
            ->icon('Copy')
            ->color('blue')
            ->method('POST')
            ->url(fn($record) => route('tenant.products.execute', ['record' => $record->id]))
            ->confirm([
                'title' => 'Duplicar Produto?',
                'message' => 'Deseja criar uma cópia deste produto?',
            ]),
            
        // Outra ação personalizada
        Action::make('export')
            ->label('Exportar')
            ->icon('Download')
            ->color('green')
            ->method('POST')
            ->url(fn($record) => route('tenant.products.execute', ['record' => $record->id])),
    ]);

    return $table;
}
```

## Configurações do Action.php

### Métodos Disponíveis

```php
Action::make('action-name')
    // Visual
    ->label('Rótulo da Ação')
    ->icon('IconName')  // Ícone do Lucide
    ->color('blue')     // green, blue, red, yellow, gray
    ->variant('default') // default, outline, ghost, destructive, secondary
    ->size('sm')        // sm, default, lg, icon
    
    // Comportamento HTTP
    ->method('POST')    // GET, POST, PUT, PATCH, DELETE
    ->url('/custom/url')
    ->url(fn($record) => route('custom.route', $record))
    
    // Inertia.js
    ->preserveScroll(true)
    ->preserveState(false)
    ->only(['prop1', 'prop2'])
    
    // Confirmação
    ->confirm([
        'title' => 'Confirmar Ação?',
        'message' => 'Esta ação não pode ser desfeita.',
        'confirmText' => 'Sim, continuar',
        'cancelText' => 'Cancelar',
    ])
    
    // Mensagens
    ->successMessage('Ação executada!')
    ->errorMessage('Erro ao executar ação.')
    
    // Modal (para formulários)
    ->modalSize('md')   // sm, md, lg, xl
    ->columns([...]);   // Campos do formulário
```

## Frontend (ActionButton.vue)

O componente `ActionButton.vue` processa automaticamente as ações:

```vue
<template>
  <ActionRenderer :action="action" :record="record" />
</template>
```

**O que acontece ao clicar:**

1. Valida se há URL configurada
2. Prepara dados (record_id, action name)
3. Executa via Inertia.js router com método correto
4. Respeita configurações de preserveScroll/preserveState
5. Mostra mensagens de sucesso/erro
6. Emite eventos para componentes pais

## Tipos de Ações (actionType)

```php
// Link simples (GET)
->actionType('link')

// Chamada API com confirmação
->actionType('api')

// Modal com formulário
->actionType('modal')

// Executa função JavaScript
->actionType('callback')
->callback('myWindowFunction')
```

## Exemplos Práticos

### Duplicar Registro

```php
Action::make('duplicate')
    ->label('Duplicar')
    ->icon('Copy')
    ->color('blue')
    ->method('POST')
    ->confirm([
        'title' => 'Duplicar?',
        'message' => 'Criar uma cópia deste registro?',
    ]);
```

### Exportar Dados

```php
Action::make('export')
    ->label('Exportar PDF')
    ->icon('Download')
    ->color('green')
    ->method('GET')
    ->targetBlank(); // Abre em nova aba
```

### Enviar Notificação

```php
Action::make('notify')
    ->label('Notificar')
    ->icon('Bell')
    ->color('yellow')
    ->method('POST')
    ->confirm([
        'title' => 'Enviar Notificação?',
        'message' => 'Isto enviará um email para o cliente.',
    ]);
```

### Modal com Formulário

```php
Action::make('quick-edit')
    ->label('Edição Rápida')
    ->icon('Edit')
    ->actionType('modal')
    ->modalSize('lg')
    ->columns([
        TextField::make('name')->required(),
        TextField::make('email')->required(),
    ]);
```

## Fluxo Completo

```
1. Usuário clica no botão
   ↓
2. ActionButton.vue valida e prepara dados
   ↓
3. Executa via Inertia.js router
   ↓
4. Laravel roteia para controller
   ↓
5. Controller executa lógica
   ↓
6. Retorna resposta (back() ou redirect())
   ↓
7. Frontend atualiza e mostra mensagem
```

## Debug

Para debugar ações, adicione logs:

```php
// No controller
\Log::info('Executando ação', [
    'action' => $actionName,
    'record_id' => $validated['record_id'],
    'user' => auth()->id(),
]);
```

```javascript
// No ActionButton.vue (já incluído)
console.log('Action URL:', props.action.url)
console.log('Action Method:', props.action.method)
```

## Boas Práticas

1. **Sempre validar** os dados recebidos no controller
2. **Use match()** para mapear ações (PHP 8+)
3. **Adicione confirmação** para ações destrutivas
4. **Retorne mensagens** claras de sucesso/erro
5. **Use jobs** para operações longas
6. **Autorize** as ações com policies
7. **Registre logs** de ações importantes

## Troubleshooting

### Ação não executa
- Verifique se a rota está registrada
- Confirme que a URL está correta
- Valide as permissões do usuário

### Erro de validação
- Certifique-se que record_id existe
- Valide o nome da ação

### Modal não abre
- Confirme que actionType='modal'
- Verifique se há columns definidos
