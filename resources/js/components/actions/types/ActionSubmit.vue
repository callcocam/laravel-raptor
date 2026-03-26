<!--
 * ActionButton - Componente de botão de ação padrão
 *
 * Renderiza um botão com ícone e label
 * Suporta diferentes variantes e cores
 -->
<template>
  <Button
    v-if="!isActionStyle"
    :variant="computedVariant"
    :size="computedSize"
    :as-child="asChild"
    :class="cn('gap-1.5', className)"
    @click="handleClick"
  >
    <ActionIconBox v-if="iconComponent" :variant="iconBoxVariant">
      <component :is="iconComponent" />
    </ActionIconBox>
    <span class="text-xs">{{ action.label }}</span>
  </Button>
  <button
    v-else 
    :class="cn(actionStyle.buttonClasses, className)"
    @click="handleClick"
  >
    <div v-if="iconComponent" :class="actionStyle.iconWrapperClasses">
      <component :is="iconComponent" :class="actionStyle.iconClasses" />
    </div>
    <span :class="actionStyle.labelClasses">{{ action.label }}</span>
  </button>
</template>

<script setup lang="ts">
import { Button } from '~/components/ui/button'
import ActionIconBox from '~/components/ui/ActionIconBox.vue'
import { cn } from '~/lib/utils'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

interface Props {
    action: TableAction
    variant?: 'default' | 'create' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link' | 'success' | 'warning'
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
const { variant: computedVariant, size: computedSize, iconComponent, isActionStyle, actionStyle, iconBoxVariant } = useActionUI({
    action: props.action,
    defaultSize: 'sm',
    defaultVariant: props.variant
})

// Handler de clique
const handleClick = () => {
    emit('click')
}
</script> 
