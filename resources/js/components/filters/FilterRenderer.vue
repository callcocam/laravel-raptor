<!--
 * FilterRenderer - Componente dinâmico para renderizar filtros
 * 
 * Renderiza diferentes tipos de filtros baseado no campo 'component'
 * Similar ao InfoReander.vue, usa FilterRegistry para componentes customizados
 -->
<template>
  <component 
    :is="component" 
    :filter="filter"
    :modelValue="modelValue"
    @update:modelValue="$emit('update:modelValue', $event)"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import FilterRegistry from '../../utils/FilterRegistry'

interface Props {
  filter: {
    name: string
    label: string
    type: string
    component?: string
    options?: Array<{ value: string | number, label: string }>
    placeholder?: string
    [key: string]: any
  }
  modelValue?: any
}

const props = defineProps<Props>()

defineEmits<{
  (e: 'update:modelValue', value: any): void
}>()

/**
 * Obtém o componente a ser renderizado do FilterRegistry
 * 
 * Usa o campo 'component' do filtro (ex: 'filter-text')
 * Fallback para 'filter-{type}' baseado no tipo
 * Fallback final para 'filter-text' se não encontrado
 */
const component = computed(() => {
  // Prioridade: component customizado do filtro
  if (props.filter.component) {
    const customComponent = FilterRegistry.get(props.filter.component)
    if (customComponent) {
      return customComponent
    }
  }

  // Fallback: componente baseado no tipo do filtro
  const typeComponentName = `filter-${props.filter.type}`
  const typeComponent = FilterRegistry.get(typeComponentName)
  
  if (typeComponent) {
    return typeComponent
  }

  // Fallback final: filter-text
  const fallback = FilterRegistry.get('filter-text')

  if (!fallback) {
    console.warn(
      `Filter component '${props.filter.component || typeComponentName}' not found in registry and no fallback available`
    )
  }

  return fallback
})
</script>
