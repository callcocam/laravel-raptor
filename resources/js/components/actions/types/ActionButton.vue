<!--
 * ActionButton - Componente de botão de ação padrão
 *
 * Renderiza um botão com ícone e label
 * Suporta diferentes variantes e cores
 -->
<template>
  <Button
    :variant="computedVariant"
    :size="computedSize"
    :as-child="asChild"
    :class="cn('gap-1.5', className)"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span class="text-xs">{{ action.label }}</span>
  </Button>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import * as LucideIcons from 'lucide-vue-next'
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

// Variant computado - usa prop ou mapeia da cor
const computedVariant = computed(() => {
  if (props.variant) return props.variant
  
  const colorMap: Record<string, any> = {
    'green': 'default',
    'blue': 'default',
    'red': 'destructive',
    'yellow': 'outline',
    'gray': 'secondary',
    'default': 'default'
  }

  return colorMap[props.action.color || 'default'] || 'default'
})

// Size computado - usa prop ou padrão
const computedSize = computed(() => props.size)

// Classes do ícone
const iconClasses = computed(() => {
  const sizeMap: Record<string, string> = {
    'sm': 'h-3 w-3',
    'default': 'h-3.5 w-3.5',
    'lg': 'h-4 w-4',
    'icon': 'h-4 w-4'
  }
  return sizeMap[props.size] || 'h-3.5 w-3.5'
})
 
// Componente do ícone dinâmico
const iconComponent = computed(() => {
  if (!props.action.icon) return null

  // Tenta obter o ícone do Lucide
  const IconComponent = (LucideIcons as any)[props.action.icon]

  if (!IconComponent) {
    console.warn(`Icon "${props.action.icon}" not found in lucide-vue-next`)
    return null
  }

  return h(IconComponent)
})

// Handler de clique
const handleClick = () => {
  emit('click')
}
</script>
