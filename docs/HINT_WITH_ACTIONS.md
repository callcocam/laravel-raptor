# Hint com Actions - Guia de Uso

## Visão Geral

O sistema de hints foi melhorado para suportar não apenas texto, mas também **actions** (botões, links, etc.) que podem ser exibidos ao lado do label do campo.

## Backend (PHP)

### Configuração Básica

```php
use Callcocam\LaravelRaptor\Forms\Fields\Text;
use Callcocam\LaravelRaptor\Actions\Action;

// Hint como texto simples
Text::make('name', 'Nome')
    ->hint('Digite seu nome completo')

// Hint com múltiplas actions
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
    ])
```

### Métodos Disponíveis

#### `hint(string|array|Closure $hint): static`

Define um hint que pode ser:
- **String**: Texto simples de ajuda
- **Array**: Lista de actions a serem renderizadas
- **Closure**: Função que retorna string ou array

```php
// String
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

// Closure com actions condicionais
->hint(fn () => auth()->user()->isAdmin() 
    ? [
        Action::make('edit')->label('Editar')->icon('Edit'),
        Action::make('delete')->label('Excluir')->icon('Trash'),
      ]
    : []
)
```

#### `hintActions(array|Closure $actions): static`

Alias mais semântico para `hint()` quando usado com array de actions.

```php
Text::make('document', 'Documento')
    ->hintActions([
        Action::make('validate')
            ->label('Validar')
            ->icon('CheckCircle')
            ->color('green')
            ->confirm()
            ->url(fn ($record) => "/documents/{$record->id}/validate"),
        
        Action::make('download')
            ->label('Baixar')
            ->icon('Download')
            ->color('blue')
            ->url(fn ($record) => "/documents/{$record->id}/download"),
    ])
```

### Traits Disponíveis

O `BelongsToHelpers` trait fornece:

- `hint(string|array|Closure $hint)` - Define hint
- `hintActions(array|Closure $actions)` - Define hint com actions
- `getHint(): string|array|null` - Retorna hint configurado

## Frontend (Vue)

### Componentes

#### HintRenderer.vue

Componente reutilizável que renderiza hints automaticamente.

```vue
<template>
  <HintRenderer :hint="column.hint" />
</template>

<script setup>
import HintRenderer from '~/components/form/HintRenderer.vue'
</script>
```

O `HintRenderer` detecta automaticamente:
- Se `hint` é string → renderiza como texto
- Se `hint` é array → renderiza cada action com `ActionRenderer`

### Uso nos Campos de Formulário

Todos os campos de formulário já suportam hint com actions:

```vue
<!-- FormFieldText.vue -->
<FieldLabel v-if="column.label">
  <div class="flex items-center justify-between w-full">
    <div>
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </div>
    <HintRenderer v-if="column.hint" :hint="column.hint" class="ml-2" />
  </div>
</FieldLabel>
```

### Interface TypeScript

```typescript
interface FormColumn {
  name: string
  label?: string
  hint?: string | any[] // String ou array de actions
  // ... outros campos
}
```

## Exemplos Práticos

### 1. Campo com Validação

```php
Text::make('cpf', 'CPF')
    ->mask('###.###.###-##')
    ->hintActions([
        Action::make('validate')
            ->label('Validar')
            ->icon('CheckCircle')
            ->color('green')
            ->url('/validate-cpf')
            ->method('post'),
    ])
```

### 2. Campo com Múltiplas Ações

```php
Text::make('address', 'Endereço')
    ->hintActions([
        Action::make('search-cep')
            ->label('Buscar CEP')
            ->icon('Search')
            ->color('blue')
            ->callback('searchCep'),
        
        Action::make('clear')
            ->label('Limpar')
            ->icon('X')
            ->color('gray')
            ->callback('clearAddress'),
        
        Action::make('map')
            ->label('Ver no Mapa')
            ->icon('MapPin')
            ->color('green')
            ->url(fn ($record) => "https://maps.google.com/?q={$record->address}"),
    ])
```

### 3. Hint Condicional

```php
Text::make('balance', 'Saldo')
    ->hint(fn ($record) => 
        $record->balance < 0 
            ? [
                Action::make('add-funds')
                    ->label('Adicionar Fundos')
                    ->icon('Plus')
                    ->color('green')
                    ->url('/add-funds'),
              ]
            : 'Saldo positivo'
    )
```

### 4. Hint com Permissões

```php
Text::make('status', 'Status')
    ->hintActions(fn () => auth()->user()->can('change-status')
        ? [
            Action::make('approve')
                ->label('Aprovar')
                ->icon('Check')
                ->color('green')
                ->confirm('Aprovar este item?')
                ->url('/approve'),
            
            Action::make('reject')
                ->label('Rejeitar')
                ->icon('X')
                ->color('red')
                ->confirm('Rejeitar este item?')
                ->url('/reject'),
          ]
        : []
    )
```

## Boas Práticas

### ✅ Fazer

- Use `hint()` para textos simples
- Use `hintActions()` para actions (mais semântico)
- Mantenha as actions relacionadas ao campo
- Use ícones para melhor UX
- Use cores para indicar tipo de ação (verde=positivo, vermelho=negativo)

### ❌ Evitar

- Não coloque muitas actions (máx 3-4)
- Não use actions para funcionalidades complexas
- Evite hints muito longos como string

## Estilo Visual

As actions no hint são renderizadas como botões pequenos alinhados à direita do label:

```
[Nome do Campo *]                    [🔍 Buscar] [✓ Validar]
[_____________________________________]
```

## Componentes Relacionados

- `HintRenderer.vue` - Renderiza hint como texto ou actions
- `ActionRenderer.vue` - Renderiza uma action individual
- `AddonsContext.vue` - Gerencia addons de prepend/append
- `BelongsToHelpers.php` - Trait com métodos de hint

## Migração

Se você tinha código usando hint apenas com string:

```php
// Antes
->hint('Texto de ajuda')

// Depois (ainda funciona!)
->hint('Texto de ajuda')

// Novo: Com actions
->hintActions([
    Action::make('help')->label('Ajuda')->icon('HelpCircle'),
])
```

**Nenhuma breaking change!** O código antigo continua funcionando.
