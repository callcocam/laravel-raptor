<!--
 * ActionCallback - Componente para executar ações via callback JavaScript
 *
 * Executa uma função JavaScript registrada no window sem fazer chamada ao backend
 * Útil para ações client-side como print, export, etc.
 -->
<template>
  <Button
    :variant="variant"
    :size="size"
    :class="className"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span>{{ action.label }}</span>
  </Button>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import { Button } from '@/components/ui/button'
import * as LucideIcons from 'lucide-vue-next'
import { useAction } from '~/composables/useAction'
import type { TableAction } from '~/types/table'

interface Props {
  action: TableAction
  record?: any
  size?: 'default' | 'sm' | 'lg' | 'icon'
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'default'
})

const emit = defineEmits<{
  (e: 'success'): void
  (e: 'error', error: any): void
}>()

const { executeCallback } = useAction()

// Mapeia cor para variant do shadcn
const variant = computed(() => {
  const colorMap: Record<string, any> = {
    'green': 'default',
    'blue': 'default',
    'red': 'destructive',
    'yellow': 'warning',
    'gray': 'secondary',
    'default': 'default'
  }

  return colorMap[props.action.color || 'default'] || 'default'
})

// Classes do ícone
const iconClasses = computed(() => {
  return props.size === 'sm' ? 'h-3 w-3 mr-1.5' : 'h-4 w-4 mr-2'
})

// Componente do ícone dinâmico
const iconComponent = computed(() => {
  if (!props.action.icon) return null

  const IconComponent = (LucideIcons as any)[props.action.icon]

  if (!IconComponent) {
    console.warn(`Icon "${props.action.icon}" not found in lucide-vue-next`)
    return null
  }

  return h(IconComponent)
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
