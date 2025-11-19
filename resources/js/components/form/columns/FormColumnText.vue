<!--
 * FormColumnText - Componente de campo de texto
 *
 * Campo de input de texto básico para formulários
 -->
<template>
  <FieldWrapper :column="column" :error="error">
    <Input
      :id="column.name"
      :name="column.name"
      :type="column.type || 'text'"
      :placeholder="column.placeholder || column.label"
      :required="column.required"
      :disabled="column.disabled"
      :modelValue="internalValue"
      @update:modelValue="updateValue"
      :class="[hasError ? 'border-destructive' : '', inputClass]"
    />
  </FieldWrapper>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Input } from '@/components/ui/input'
import FieldWrapper from '../FieldWrapper.vue'

interface FormColumn {
  name: string
  label?: string
  type?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  tooltip?: string
  helpText?: string
  hint?: string
  default?: string | number
  prepend?: string
  append?: string
  prefix?: string
  suffix?: string
}

interface Props {
  column: FormColumn
  modelValue?: string | number | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const hasError = computed(() => !!props.error)

// Classes condicionais para o input
const inputClass = computed(() => {
  const classes = []

  // Ajusta bordas se houver prepend/append
  if (props.column.prepend || props.column.prefix) {
    classes.push('rounded-l-none')
  }
  if (props.column.append || props.column.suffix) {
    classes.push('rounded-r-none')
  }

  return classes.join(' ')
})

// Valor interno com suporte a default
const internalValue = computed({
  get: () => {
    if (props.modelValue !== null && props.modelValue !== undefined) {
      return props.modelValue
    }
    return props.column.default || null
  },
  set: (value) => {
    emit('update:modelValue', value)
  }
})

const updateValue = (value: string | number | null) => {
  emit('update:modelValue', value)
}

// Emite valor padrão no mount se necessário
onMounted(() => {
  if (props.modelValue === null && props.column.default) {
    emit('update:modelValue', props.column.default)
  }
})
</script>
