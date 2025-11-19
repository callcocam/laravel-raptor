# Table Columns - Componentes de Coluna para Tabelas

Este diretório contém componentes especializados para renderizar diferentes tipos de dados em células de tabela, seguindo o mesmo padrão do InfoList.

## Componentes Disponíveis

### TableText
Renderiza texto simples.

```typescript
{
  component: 'table-column-text',
  text: 'Valor do texto'
}
```

### TableBoolean
Renderiza valores booleanos com ícone e cor.

```typescript
{
  component: 'table-column-boolean',
  text: 'Ativo',
  icon: 'check-circle',
  color: 'success', // success | muted
  tooltip: 'Status ativo'
}
```

### TableDate
Renderiza datas formatadas.

```typescript
{
  component: 'table-column-date',
  text: '19/11/2025',
  tooltip: 'Data de criação'
}
```

### TableEmail
Renderiza emails com link clicável.

```typescript
{
  component: 'table-column-email',
  text: 'user@example.com',
  tooltip: 'Enviar email'
}
```

### TablePhone
Renderiza telefones com link clicável.

```typescript
{
  component: 'table-column-phone',
  text: '+55 11 98765-4321',
  tooltip: 'Ligar'
}
```

### TableStatus
Renderiza status com badge colorido, ícone opcional e dot opcional.

```typescript
{
  component: 'table-column-status',
  text: 'Em andamento',
  icon: 'clock',
  color: 'warning', // success | warning | danger | info | muted
  showDot: true,
  tooltip: 'Status do processo'
}
```

## Uso no Backend (Laravel)

```php
use Callcocam\LaravelRaptor\Support\Table\Column;

Column::make('status')
    ->label('Status')
    ->component('table-column-status')
    ->formatStateUsing(function ($state, $record) {
        return [
            'text' => $record->status_label,
            'color' => $record->status_color, // success, warning, danger, info, muted
            'icon' => $record->status_icon,
            'showDot' => true,
        ];
    });
```

## Registro Automático

Os componentes são registrados automaticamente no `ComponentRegistry` através do arquivo `index.ts`:

```typescript
ComponentRegistry.registerBulk({
    'table-column-text': defineAsyncComponent(() => import('~/components/table/columns/TableText.vue')),
    'table-column-email': defineAsyncComponent(() => import('~/components/table/columns/TableEmail.vue')),
    'table-column-date': defineAsyncComponent(() => import('~/components/table/columns/TableDate.vue')),
    'table-column-phone': defineAsyncComponent(() => import('~/components/table/columns/TablePhone.vue')),
    'table-column-status': defineAsyncComponent(() => import('~/components/table/columns/TableStatus.vue')),
    'table-column-boolean': defineAsyncComponent(() => import('~/components/table/columns/TableBoolean.vue')),
})
```

## Criando Componentes Customizados

Para criar um componente customizado:

1. Crie o arquivo Vue em `components/table/columns/`
2. Registre no `ComponentRegistry` (no app.ts da aplicação)
3. Use o nome registrado no backend

Exemplo:

```vue
<!-- TableCustom.vue -->
<template>
  <div class="custom-column">
    {{ column.text }}
  </div>
</template>

<script lang="ts" setup>
defineProps<{
  column: {
    text: string;
    // Outras props customizadas
  };
}>();
</script>
```

```typescript
// No app.ts da aplicação
import ComponentRegistry from '~/utils/ComponentRegistry'
import TableCustom from '~/components/table/columns/TableCustom.vue'

ComponentRegistry.register('table-column-custom', TableCustom)
```

## TableColumnRenderer

O `TableColumnRenderer` é o componente responsável por renderizar dinamicamente as colunas baseado no `ComponentRegistry`:

```vue
<TableColumnRenderer :column="column" />
```

Ele verifica qual componente deve ser usado através da propriedade `column.component` e faz fallback para `table-column-text` caso não encontre.
