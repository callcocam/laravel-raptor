<!--
 * ActionLink - Componente de link de ação
 *
 * Renderiza um link simples para navegação
 * Útil para ações GET que apenas navegam
 -->
<template>
  <Link
    :href="to"
    :target="target"
    :class="linkClasses"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" class="h-3.5 w-3.5" />
    <span>{{ action.label }}</span>
  </Link>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import { cn } from '@/lib/utils'
import * as LucideIcons from 'lucide-vue-next'
import type { TableAction } from '~/types/table'
import { Link } from '@inertiajs/vue3'

interface Props {
  action: TableAction
}

const props = defineProps<Props>()
  
const emit = defineEmits<{
  (e: 'click', event: MouseEvent): void
}>() 
// Target do link
const target = computed(() => {
  return props.action.target === 'modal' ? '_self' : props.action.target
})

const to = computed(() => { 
  return props.action.url || '#'
})
// Classes do link
const linkClasses = computed(() => {
  const colorClasses: Record<string, string> = {
    'green': 'text-green-600 hover:text-green-700 dark:text-green-400',
    'blue': 'text-blue-600 hover:text-blue-700 dark:text-blue-400',
    'red': 'text-red-600 hover:text-red-700 dark:text-red-400',
    'gray': 'text-gray-600 hover:text-gray-700 dark:text-gray-400',
    'default': 'text-primary hover:text-primary/80'
  }

  const colorClass = colorClasses[props.action.color || 'default'] || colorClasses.default

  return cn(
    'inline-flex items-center gap-1 font-medium text-xs transition-colors rounded-md px-2 py-1',
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
    colorClass
  )
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

// Handler de clique
const handleClick = (event: MouseEvent) => {
  emit('click', event)
}
</script>
