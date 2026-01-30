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
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span class="text-xs">{{ action.label }}</span>
  </Link>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { useActionUI } from '~/composables/useActionUI'
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
const target = props.action.target === 'modal' ? '_self' : props.action.target

const to = props.action.url || '#'

const { iconComponent, iconClasses, colorClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})

// Classes do link
const linkClasses = computed(() => cn(
  'inline-flex items-center gap-1.5 font-medium transition-colors rounded-md px-2 py-1',
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
  colorClasses.value
))

// Handler de clique
const handleClick = (event: MouseEvent) => {
  emit('click', event)
}
</script>
