# useActionUI - Composable de UI para Actions

## Visão Geral

O `useActionUI` é um composable que centraliza toda a lógica repetida de UI nos componentes de ações, eliminando duplicação de código e mantendo consistência.

## Problema Antes

Cada componente de ação duplicava a mesma lógica:

```vue
<script setup>
// ❌ Repetido em 8+ componentes
const variant = computed(() => {
  const colorMap = {
    'green': 'default',
    'blue': 'default',
    'red': 'destructive',
    // ...
  }
  return colorMap[action.color || 'default'] || 'default'
})

const iconClasses = computed(() => {
  const sizeMap = {
    'sm': 'h-3 w-3',
    'default': 'h-3.5 w-3.5',
    // ...
  }
  return sizeMap[size] || 'h-3.5 w-3.5'
})

const iconComponent = computed(() => {
  const IconComponent = LucideIcons[action.icon]
  return h(IconComponent)
})
</script>
```

## Solução Atual

Um único composable com toda a lógica:

```typescript
export function useActionUI(options: UseActionUIOptions): UseActionUIResult {
  const { action, defaultSize, defaultVariant } = options
  
  const variant = computed(() => {
    // Centralizado!
  })
  
  const iconComponent = computed(() => {
    // Centralizado!
  })
  
  // ... etc
}
```

## Como Usar

### Básico

```vue
<script setup lang="ts">
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

interface Props {
  action: TableAction
  size?: 'default' | 'sm' | 'lg' | 'icon'
}

const props = defineProps<Props>()

// Use o composable
const { variant, size, iconComponent, iconClasses, colorClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})
</script>

<template>
  <Button :variant="variant" :size="size">
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span>{{ action.label }}</span>
  </Button>
</template>
```

### Com Variant Customizada

```typescript
const { variant, size, iconComponent, iconClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm',
  defaultVariant: 'outline' // ← Override padrão
})
```

## O Que o Composable Retorna

```typescript
interface UseActionUIResult {
  variant: ComputedRef<'default' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link'>
  size: ComputedRef<'default' | 'sm' | 'lg' | 'icon'>
  iconComponent: ComputedRef<any>
  iconClasses: ComputedRef<string>
  colorClasses: ComputedRef<string>
}
```

### variant

Mapeia a cor de `action.color` para uma variant do shadcn Button:

```typescript
// Mapeamento automático
'green'   → 'default'
'blue'    → 'default'
'red'     → 'destructive'
'yellow'  → 'outline'
'gray'    → 'secondary'
```

**Prioridade:**
1. `action.variant` (explícito)
2. `defaultVariant` (passado como opção)
3. Mapeamento de cor
4. Padrão: `'default'`

### size

Retorna o tamanho do botão com prioridade:

1. `action.size`
2. `defaultSize`

### iconComponent

Carrega dinamicamente o ícone do Lucide:

```typescript
// Automático!
<component :is="iconComponent" />
```

Valida se o ícone existe e exibe aviso se não encontrado.

### iconClasses

Retorna as classes Tailwind do ícone baseado no `size`:

```typescript
'sm'      → 'h-3 w-3'
'default' → 'h-3.5 w-3.5'
'lg'      → 'h-4 w-4'
'icon'    → 'h-4 w-4'
```

### colorClasses

Retorna as classes Tailwind de cor para links:

```typescript
// Exemplo
'red' → 'text-red-600 hover:text-red-700 dark:text-red-400'
```

## Componentes Refatorados

- ✅ ActionButton.vue
- ✅ ActionCallback.vue
- ✅ ActionLink.vue
- ✅ ActionConfirm.vue
- ✅ ActionModalSlideover.vue
- ✅ ActionButtonLink.vue
- ✅ ActionDropdown.vue
- ✅ ActionFormButton.vue
- ✅ ActionSubmit.vue

## Benefícios

| Antes | Depois |
|-------|--------|
| 200+ linhas de código repetido | Centralizado em 1 composable |
| Lógica de mapeamento em 8 componentes | 1 única fonte da verdade |
| Inconsistências potenciais | Consistência garantida |
| Difícil manter | Fácil de manter e estender |

## Adicionando Novas Cores

Para adicionar suporte a uma nova cor, edite `useActionUI.ts`:

```typescript
const colorToVariantMap: Record<string, any> = {
  'green': 'default',
  'blue': 'default',
  'red': 'destructive',
  'cyan': 'default', // ← Nova cor
  // ...
}

const colorToTextClassMap: Record<string, string> = {
  // ...
  'cyan': 'text-cyan-600 hover:text-cyan-700 dark:text-cyan-400',
}
```

Automaticamente funciona em todos os 8+ componentes!

## Helper Functions

### getIconClassesWithMargin

Adiciona margem ao ícone se houver label:

```typescript
import { getIconClassesWithMargin } from '~/composables/useActionUI'

const iconClassesWithMargin = getIconClassesWithMargin(
  iconClasses.value,
  true,     // tem label?
  'before'  // posição do ícone
)

// Resultado: 'h-3 w-3 mr-1.5'
```

## Exemplos Completos

### ActionButton.vue

```vue
<template>
  <Button 
    :variant="computedVariant" 
    :size="computedSize" 
    :disabled="isExecuting"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span>{{ action.label }}</span>
  </Button>
</template>

<script setup lang="ts">
import { useActionUI } from '~/composables/useActionUI'

const { variant: computedVariant, size: computedSize, iconComponent, iconClasses } = 
  useActionUI({
    action: props.action,
    defaultSize: 'sm',
    defaultVariant: props.variant
  })
</script>
```

### ActionLink.vue

```vue
<template>
  <Link :href="to" :class="linkClasses">
    <component v-if="iconComponent" :is="iconComponent" class="h-3.5 w-3.5" />
    <span>{{ action.label }}</span>
  </Link>
</template>

<script setup lang="ts">
import { useActionUI } from '~/composables/useActionUI'

const { iconComponent, colorClasses } = useActionUI({
  action: props.action
})

const linkClasses = cn(
  'inline-flex items-center gap-1 font-medium text-xs',
  colorClasses.value
)
</script>
```

## Performance

O composable usa `computed()` para lazy evaluation:

- ✅ Cálculos só ocorrem quando necessário
- ✅ Sem re-renders desnecessários
- ✅ Eficiente em listas grandes

## Testes

```typescript
import { useActionUI } from '~/composables/useActionUI'

describe('useActionUI', () => {
  it('mapeia verde para variant default', () => {
    const { variant } = useActionUI({
      action: { color: 'green' } as any,
    })
    expect(variant.value).toBe('default')
  })

  it('mapeia vermelho para destructive', () => {
    const { variant } = useActionUI({
      action: { color: 'red' } as any,
    })
    expect(variant.value).toBe('destructive')
  })

  it('carrega ícone correto do Lucide', () => {
    const { iconComponent } = useActionUI({
      action: { icon: 'Copy' } as any,
    })
    expect(iconComponent.value).toBeDefined()
  })
})
```

## Futuro

Possíveis extensões:

- [ ] Tema customizável (light/dark)
- [ ] Animações de ícone
- [ ] Variações de tamanho customizáveis
- [ ] Suporte a ícones customizados
- [ ] Acessibilidade avançada (aria-labels dinâmicos)
