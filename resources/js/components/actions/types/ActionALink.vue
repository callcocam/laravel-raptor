<!--
 * ActionLink - Componente de link de ação
 *
 * Renderiza um link simples para navegação
 * Útil para ações GET que apenas navegam
 -->
<template>
  <a
    :href="action.url"
    :target="target"
    :class="linkClasses"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span class="text-xs">{{ action.label }}</span>
  </a>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

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

// Usa composable para UI padronizada
const { iconComponent, iconClasses, colorClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})

// Classes do link
const linkClasses = computed(() => {
  return cn(
    'inline-flex items-center gap-1.5 font-medium transition-colors rounded-md px-2 py-1',
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
    colorClasses.value
  )
})

// Handler de clique
const handleClick = (event: MouseEvent) => {
  emit('click', event)
}
</script>
