# Hints com Actions

O sistema de hints suporta não apenas texto, mas também ações (botões, links) ao lado do label do campo.

## Uso Básico

### Hint como Texto

```php
use Callcocam\LaravelRaptor\Forms\Fields\Text;

Text::make('name', 'Nome')
    ->hint('Digite seu nome completo');
```

### Hint com Actions

```php
use Callcocam\LaravelRaptor\Forms\Fields\Text;
use Callcocam\LaravelRaptor\Support\Actions\Action;

Text::make('email', 'E-mail')
    ->hintActions([
        Action::make('verify')
            ->label('Verificar')
            ->icon('CheckCircle')
            ->color('blue')
            ->url('/verify-email'),
        
        Action::make('resend')
            ->label('Reenviar')
            ->icon('Mail')
            ->color('gray')
            ->method('post')
            ->url('/resend-verification'),
    ]);
```

## Métodos Disponíveis

### hint()

Define um hint que pode ser:

```php
// String simples
->hint('Texto de ajuda')

// Array de actions
->hint([
    Action::make('help')->label('Ajuda')->icon('HelpCircle'),
])

// Closure dinâmica
->hint(fn () => auth()->user()->isAdmin() 
    ? 'Admin pode editar tudo' 
    : 'Usuário comum tem restrições'
)
```

### hintActions()

Atalho para array de actions:

```php
->hintActions([
    Action::make('action1'),
    Action::make('action2'),
])
```

## Exemplos Práticos

### Verificação de Email

```php
Text::make('email', 'E-mail')
    ->required()
    ->email()
    ->hintActions([
        Action::make('verify')
            ->label('Verificar')
            ->icon('CheckCircle')
            ->color('green')
            ->visible(fn ($record) => !$record->email_verified_at)
            ->method('post')
            ->url(fn ($record) => route('email.verify', $record)),
    ]);
```

### Upload com Preview

```php
FileUpload::make('document', 'Documento')
    ->hintActions([
        Action::make('preview')
            ->label('Visualizar')
            ->icon('Eye')
            ->visible(fn ($record) => $record->document)
            ->url(fn ($record) => Storage::url($record->document))
            ->openInNewTab(),
            
        Action::make('download')
            ->label('Download')
            ->icon('Download')
            ->visible(fn ($record) => $record->document)
            ->url(fn ($record) => route('documents.download', $record)),
    ]);
```

### Campo com Ajuda Contextual

```php
Select::make('category_id', 'Categoria')
    ->options(Category::pluck('name', 'id'))
    ->hintActions([
        Action::make('create_category')
            ->label('Nova Categoria')
            ->icon('Plus')
            ->color('blue')
            ->url(route('categories.create'))
            ->openInNewTab(),
            
        Action::make('help')
            ->label('?')
            ->icon('HelpCircle')
            ->color('gray')
            ->tooltip('Selecione a categoria principal do produto'),
    ]);
```

### Sincronização de Dados

```php
Text::make('external_id', 'ID Externo')
    ->disabled()
    ->hintActions([
        Action::make('sync')
            ->label('Sincronizar')
            ->icon('RefreshCw')
            ->color('blue')
            ->method('post')
            ->url(fn ($record) => route('sync.external', $record))
            ->confirm([
                'title' => 'Sincronizar dados?',
                'message' => 'Isso irá atualizar os dados do sistema externo.',
            ]),
    ]);
```

## Frontend (Vue)

O componente renderiza as actions automaticamente:

```vue
<template>
    <div class="form-group">
        <div class="flex items-center justify-between">
            <Label>{{ field.label }}</Label>
            
            <!-- Hint como texto -->
            <span v-if="typeof field.hint === 'string'" class="text-sm text-muted-foreground">
                {{ field.hint }}
            </span>
            
            <!-- Hint como actions -->
            <div v-else-if="Array.isArray(field.hint)" class="flex gap-2">
                <ActionButton
                    v-for="action in field.hint"
                    :key="action.name"
                    :action="action"
                    size="sm"
                />
            </div>
        </div>
        
        <Input v-model="modelValue" />
    </div>
</template>
```
