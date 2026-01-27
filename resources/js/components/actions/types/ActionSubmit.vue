<!--
 * ActionButton - Componente de botão de ação padrão
 *
 * Renderiza um botão com ícone e label
 * Suporta diferentes variantes e cores
 -->
<template>
    <Button :variant="computedVariant" :size="computedSize" :as-child="asChild" :class="cn('gap-1.5', className)"
        @click="handleClick">
        <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
        <span class="text-xs">{{ action.label }}</span>
    </Button>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

interface Props {
    action: TableAction
    variant?: 'default' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link'
    size?: 'default' | 'sm' | 'lg' | 'icon'
    asChild?: boolean
    className?: string
}

const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    asChild: false
})

const emit = defineEmits<{
    (e: 'click'): void
}>()

// Usa composable para UI
const { variant: computedVariant, size: computedSize, iconComponent, iconClasses } = useActionUI({
    action: props.action,
    defaultSize: 'sm',
    defaultVariant: props.variant
})

// Handler de clique
const handleClick = () => {
    emit('click')
}
</script> 