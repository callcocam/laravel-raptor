# useActionUI Composable

O `useActionUI` centraliza a lógica de UI para componentes de ações, eliminando duplicação de código.

## O Problema (Antes)

Cada componente de ação tinha código duplicado:

```vue
<script setup>
// ❌ Repetido em 8+ componentes
const variant = computed(() => {
    const colorMap = {
        'green': 'default',
        'blue': 'default',
        'red': 'destructive',
    }
    return colorMap[action.color || 'default'] || 'default'
})

const iconClasses = computed(() => {
    const sizeMap = {
        'sm': 'h-3 w-3',
        'default': 'h-3.5 w-3.5',
    }
    return sizeMap[size] || 'h-3.5 w-3.5'
})

const iconComponent = computed(() => {
    const IconComponent = LucideIcons[action.icon]
    return h(IconComponent)
})
</script>
```

## A Solução (Depois)

Um único composable com toda a lógica:

```ts
// composables/useActionUI.ts
import { useActionUI } from '@/composables/useActionUI'

const { variant, size, iconComponent, iconClasses, colorClasses, buttonClasses } = useActionUI({
    action: props.action,
    defaultSize: 'sm',
})
```

## Uso Básico

```vue
<script setup lang="ts">
import { useActionUI } from '@/composables/useActionUI'

interface Props {
    action: {
        label: string
        icon?: string
        color?: string
        variant?: string
        size?: string
    }
}

const props = defineProps<Props>()

const { variant, size, iconComponent, iconClasses } = useActionUI({
    action: props.action,
    defaultSize: 'sm',
})
</script>

<template>
    <Button :variant="variant" :size="size">
        <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
        {{ action.label }}
    </Button>
</template>
```

## API Completa

### Opções

```ts
interface UseActionUIOptions {
    action: {
        icon?: string
        color?: string
        variant?: string
        size?: string
    }
    defaultSize?: 'sm' | 'default' | 'lg' | 'icon'
    defaultVariant?: 'default' | 'outline' | 'destructive' | 'secondary' | 'ghost'
}
```

### Retorno

```ts
interface UseActionUIResult {
    // Variante do botão (default, outline, destructive, etc.)
    variant: ComputedRef<string>
    
    // Tamanho (sm, default, lg, icon)
    size: ComputedRef<string>
    
    // Componente do ícone (VNode ou null)
    iconComponent: ComputedRef<VNode | null>
    
    // Classes CSS para o ícone
    iconClasses: ComputedRef<string>
    
    // Classes de cor para links/textos
    colorClasses: ComputedRef<string>
    
    // Classes completas para botão
    buttonClasses: ComputedRef<string>
}
```

## Mapeamento de Cores para Variantes

```ts
const colorToVariantMap = {
    'green': 'default',
    'blue': 'default',
    'red': 'destructive',
    'yellow': 'warning',
    'gray': 'secondary',
    'default': 'default',
}
```

## Mapeamento de Tamanhos para Classes de Ícone

```ts
const sizeToIconClassMap = {
    'sm': 'h-3 w-3',
    'default': 'h-3.5 w-3.5',
    'lg': 'h-4 w-4',
    'icon': 'h-4 w-4',
}
```

## Exemplos de Uso

### ActionButton

```vue
<script setup>
import { useActionUI } from '@/composables/useActionUI'

const props = defineProps<{ action: Action }>()

const { variant, size, iconComponent, iconClasses } = useActionUI({
    action: props.action,
    defaultSize: 'sm',
})
</script>

<template>
    <Button :variant="variant" :size="size" @click="handleClick">
        <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
        <span>{{ action.label }}</span>
    </Button>
</template>
```

### ActionLink

```vue
<script setup>
import { useActionUI } from '@/composables/useActionUI'

const props = defineProps<{ action: Action }>()

const { iconComponent, iconClasses, colorClasses } = useActionUI({
    action: props.action,
})
</script>

<template>
    <Link :href="action.url" :class="colorClasses">
        <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
        {{ action.label }}
    </Link>
</template>
```

### ActionDropdown

```vue
<script setup>
import { useActionUI } from '@/composables/useActionUI'

const props = defineProps<{ actions: Action[] }>()

// Para cada ação no dropdown
const getActionUI = (action: Action) => useActionUI({ action })
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger>
            <Button variant="ghost" size="icon">
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent>
            <DropdownMenuItem 
                v-for="action in actions" 
                :key="action.name"
                @click="handleAction(action)"
            >
                <component 
                    v-if="getActionUI(action).iconComponent.value" 
                    :is="getActionUI(action).iconComponent.value" 
                    :class="getActionUI(action).iconClasses.value" 
                />
                {{ action.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
```

## Redução de Código

| Componente | Antes | Depois | Redução |
|------------|-------|--------|---------|
| ActionButton | 70 linhas | 45 linhas | 35% |
| ActionCallback | 100 linhas | 35 linhas | 65% |
| ActionLink | 70 linhas | 35 linhas | 50% |
| ActionDropdown | 100 linhas | 40 linhas | 60% |
| **Total** | 930 linhas | 562 linhas | **40%** |
