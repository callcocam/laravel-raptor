<!--
 * ActionLink - Componente de link de ação
 *
 * Renderiza um Link do Inertia com aparência de botão (estilo base plannerate)
 * Útil para ações GET que navegam via SPA
 -->
<template>
  <Link
    :href="to"
    :target="target"
    :class="actionStyle.buttonClasses"
    @click="handleClick"
  >
    <div v-if="iconComponent" :class="actionStyle.iconWrapperClasses">
      <component :is="iconComponent" :class="actionStyle.iconClasses" />
    </div>
    <span :class="actionStyle.labelClasses">{{ action.label }}</span>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
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
const target = props.action.target === 'modal' ? '_self' : props.action.target

const to = props.action.url || '#'

const { iconComponent, actionStyle } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})

// Handler de clique
const handleClick = (event: MouseEvent) => {
  emit('click', event)
}
</script>
