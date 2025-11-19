<template>
  <component
    :is="component"
    :column="column"
    :error="error"
    v-model="internalValue"
  />
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import ComponentRegistry from '../../../utils/ComponentRegistry'

interface Props {
  column: {
    name: string
    component?: string
    [key: string]: any
  }
  modelValue?: any
  error?: string | string[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
}>()

/**
 * Obtém o componente a ser renderizado do ComponentRegistry
 *
 * Usa o campo 'component' da coluna (ex: 'form-column-file-upload')
 * Fallback para 'form-column-text' se não encontrado
 */
const component = computed(() => {
  const componentName = props.column.component || 'form-column-text'

  // Tenta obter do registry
  const registeredComponent = ComponentRegistry.get(componentName)

  if (registeredComponent) {
    return registeredComponent
  }

  // Fallback para componente padrão
  const fallback = ComponentRegistry.get('form-column-text')

  if (!fallback) {
    console.warn(`Component '${componentName}' not found in registry and no fallback available`)
  }

  return fallback
})

/**
 * Gerencia o v-model two-way binding
 */
const internalValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})
</script>
