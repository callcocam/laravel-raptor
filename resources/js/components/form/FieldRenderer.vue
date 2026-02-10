<template>
  <component
    :is="component"
    :column="column"
    :index="index"
    :error="error"
    v-model="internalValue"
  />
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { ComponentRegistry } from '~/raptor'
import type { FormColumn, FieldEmitValue } from '~/types/form'

interface Props {
  column: FormColumn
  modelValue?: any
  error?: string | string[]
  index?: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: FieldEmitValue): void
}>() 
/**
 * Obtém o componente a ser renderizado do ComponentRegistry
 *
 * Usa o campo 'component' da coluna (ex: 'form-field-text')
 * Fallback para 'form-field-text' se não encontrado
 */
const component = computed(() => {
  const componentName = props.column.component || 'form-field-text'

  // Tenta obter do registry
  const registeredComponent = ComponentRegistry.get(componentName)

  if (registeredComponent) {
    return registeredComponent
  }

  // Fallback para componente padrão
  const fallback = ComponentRegistry.get('form-field-text')

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
