<!--
 * ActionCallback - Componente para executar ações via callback JavaScript
 *
 * Executa uma função JavaScript registrada no window sem fazer chamada ao backend
 * Útil para ações client-side como print, export, etc.
 -->
<template>
  <Button
    type="button"
    :variant="variant"
    :size="size"
    :class="cn('gap-1.5', className)"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span class="text-xs">{{ action.label }}</span>
  </Button>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { useAction } from '~/composables/useAction'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

interface Props {
  action: TableAction
  record?: any
  size?: 'default' | 'sm' | 'lg' | 'icon'
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'sm'
})

const emit = defineEmits<{
  (e: 'success'): void
  (e: 'error', error: any): void
}>()

const { executeCallback } = useAction()
const { variant, size, iconComponent, iconClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})

// Handler de clique - executa callback
const handleClick = () => {
  if (!props.action.callback) {
    console.warn('ActionCallback: No callback specified')
    return
  }

  const success = executeCallback(props.action.callback, props.action, props.record)

  if (success) {
    emit('success')
  } else {
    emit('error', new Error(`Failed to execute callback: ${props.action.callback}`))
  }
}
</script>
