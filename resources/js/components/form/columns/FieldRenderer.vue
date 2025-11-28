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
import ComponentRegistry from '../../../utils/ComponentRegistry'

interface Props {
  column: {
    name: string
    component?: string
    index?: number
    [key: string]: any
  }
  modelValue?: any
  error?: string | string[]
  index?: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
}>() 
/**
 * Obtém o componente a ser renderizado do ComponentRegistry
 *
 * Usa o campo 'component' da coluna (ex: 'form-field-text')
 * Auto-migra componentes antigos (form-column-*) para novos (form-field-*)
 * Fallback para 'form-field-text' se não encontrado
 */
const component = computed(() => {
  let componentName = props.column.component || 'form-field-text'

  // Auto-migração de componentes antigos para novos
  if (componentName.startsWith('form-column-')) {
    const newName = componentName.replace('form-column-', 'form-field-')
    if (import.meta.env.DEV) {
      console.warn(
        `[FieldRenderer] Component '${componentName}' is deprecated. ` +
        `Use '${newName}' instead. The component will be auto-migrated for now.`
      )
    }
    componentName = newName
  }

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
