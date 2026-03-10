<!--
 * ActionALink - Componente de link de ação
 *
 * Renderiza um link nativo <a> com aparência de botão (estilo base plannerate)
 * Útil para ações GET que navegam (incluindo target _blank)
 -->
<template>
  <a
    :href="action.url"
    :target="target"
    :class="actionStyle.buttonClasses"
    @click="handleClick"
  >
    <div v-if="iconComponent" :class="actionStyle.iconWrapperClasses">
      <component :is="iconComponent" :class="actionStyle.iconClasses" />
    </div>
    <span :class="actionStyle.labelClasses">{{ action.label }}</span>
  </a>
</template>

<script setup lang="ts">
import { computed } from 'vue'
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
const { iconComponent, actionStyle } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})

// Handler de clique
const handleClick = (event: MouseEvent) => {
  emit('click', event)
}
</script>
