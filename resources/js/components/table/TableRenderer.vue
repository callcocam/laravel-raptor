<!--
 * TableRenderer - Renderiza tabela dinamicamente
 * 
 * Usa TableRegistry para obter o componente correto
 * Similar ao InfoRenderer.vue
 * Repassa eventos da tabela para o componente pai
 -->
<template>
  <component 
    :is="component" 
    :resource="resource"
    :endpoint="endpoint"
    :initial-params="initialParams"
    @state-updated="emit('state-updated', $event)"
  />
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import TableRegistry from '~/utils/TableRegistry'
import type { TableQueryParams } from '~/types/table'

const props = defineProps<{
  resource: string
  endpoint?: string
  component?: string
  initialParams?: TableQueryParams
}>()

const emit = defineEmits<{
  (e: 'state-updated', state: any): void
}>()

/**
 * Obtém o componente a ser renderizado do TableRegistry
 *
 * Usa o campo 'component' da prop (ex: 'table-default')
 * Fallback para 'table-default' se não encontrado
 */
const component = computed(() => {
  const componentName = props.component || 'table-default'

  // Tenta obter do registry
  const registeredComponent = TableRegistry.get(componentName)

  if (registeredComponent) {
    return registeredComponent
  }

  // Fallback para componente padrão
  const fallback = TableRegistry.get('table-default')

  if (!fallback) {
    console.warn(`Component '${componentName}' not found in registry and no fallback available`)
  }

  return fallback
})
</script>

