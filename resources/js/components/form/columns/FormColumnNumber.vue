<!--
 * FormColumnNumber - Componente de campo numérico
 *
 * Campo para entrada de números
 -->
<template>
  <FieldWrapper :column="column">
    <Input
      :id="column.name"
      :name="column.name"
      type="number"
      :placeholder="column.placeholder || column.label"
      :required="column.required"
      :disabled="column.disabled"
      :min="column.min"
      :max="column.max"
      :step="column.step || 1"
      :modelValue="internalValue"
      @update:modelValue="updateValue"
      :class="[hasError ? 'border-destructive' : '', inputClass]"
    />

    <!-- Mensagem de erro -->
    <template v-if="errorMessage">
      <p class="text-sm text-destructive mt-1">
        {{ errorMessage }}
      </p>
    </template>
  </FieldWrapper>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Input } from '@/components/ui/input'
import FieldWrapper from '../FieldWrapper.vue'

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  min?: number
  max?: number
  step?: number
  tooltip?: string
  helpText?: string
  hint?: string
  default?: number
  prepend?: string
  append?: string
  prefix?: string
  suffix?: string
}

interface Props {
  column: FormColumn
  modelValue?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | null): void
}>()

const errorMessage = ref('')
const hasError = computed(() => !!errorMessage.value)

const inputClass = computed(() => {
  const classes = []
  if (props.column.prepend || props.column.prefix) {
    classes.push('rounded-l-none')
  }
  if (props.column.append || props.column.suffix) {
    classes.push('rounded-r-none')
  }
  return classes.join(' ')
})

const internalValue = computed({
  get: () => {
    if (props.modelValue !== null && props.modelValue !== undefined) {
      return props.modelValue
    }
    return props.column.default || null
  },
  set: (value) => updateValue(value)
})

const updateValue = (value: number | string | null) => {
  errorMessage.value = ''

  const numValue = value !== null && value !== '' ? Number(value) : null

  // Validação de mínimo
  if (props.column.min !== undefined && numValue !== null && numValue < props.column.min) {
    errorMessage.value = `Valor mínimo: ${props.column.min}`
    return
  }

  // Validação de máximo
  if (props.column.max !== undefined && numValue !== null && numValue > props.column.max) {
    errorMessage.value = `Valor máximo: ${props.column.max}`
    return
  }

  emit('update:modelValue', numValue)
}

onMounted(() => {
  if (props.modelValue === null && props.column.default !== undefined) {
    emit('update:modelValue', props.column.default)
  }
})
</script>
