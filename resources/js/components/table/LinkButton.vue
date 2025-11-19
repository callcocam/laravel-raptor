<!--
 * LinkButton - Componente de botão de ação para tabelas
 *
 * Pode ser um link, botão com confirmação, ou botão com modal
 * Baseado na estrutura TableAction
 -->
<template>
  <Button
    :variant="variantFromColor"
    :size="size"
    :class="className"
    @click="handleClick"
  >
    <component v-if="icon" :is="iconComponent" :class="iconClasses" />
    <span>{{ label }}</span>
  </Button>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import { Button } from '@/components/ui/button'
import * as LucideIcons from 'lucide-vue-next'
import type { TableAction } from '../../types/table'

interface Props {
  action: TableAction
  size?: 'default' | 'sm' | 'lg' | 'icon'
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'default'
})

const emit = defineEmits<{
  (e: 'click', action: TableAction): void
}>()

// Label e ícone
const label = computed(() => props.action.label)
const icon = computed(() => props.action.icon)

// Mapeia cor para variant do shadcn
const variantFromColor = computed(() => {
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
  if (!icon.value) return null

  // Tenta obter o ícone do Lucide
  const IconComponent = (LucideIcons as any)[icon.value]

  if (!IconComponent) {
    console.warn(`Icon "${icon.value}" not found in lucide-vue-next`)
    return null
  }

  return h(IconComponent)
})

// Handler de clique
const handleClick = () => {
  // Se tem confirmação, emite evento para ser tratado pelo pai
  // O pai deve mostrar um dialog de confirmação
  emit('click', props.action)
}
</script>
