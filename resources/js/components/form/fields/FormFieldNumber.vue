<!--
 * FormFieldNumber - Number input field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnNumber with improved accessibility
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <!-- Input with conditional addons -->
    <AddonsContext
      :prepend="column.prepend"
      :append="column.append"
      :prefix="column.prefix"
      :suffix="column.suffix"
      v-slot="{ inputClass: addonClass }"
    >
      <Input
        :id="column.name"
        :name="column.name"
        type="number"
        :placeholder="column.placeholder || column.label"
        :required="column.required"
        :disabled="column.disabled"
        :readonly="column.readonly"
        :min="column.min"
        :max="column.max"
        :step="column.step || 1"
        :modelValue="internalValue || undefined"
        @update:modelValue="updateValue"
        :aria-invalid="hasError"
        :class="[hasError ? 'border-destructive' : '', addonClass]"
      />
    </AddonsContext>

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed, onMounted, inject, ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import AddonsContext from '../AddonsContext.vue'
import { useFieldCalculations, type FieldCalculation } from '~/composables/useFieldCalculations'

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
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
  calculation?: FieldCalculation
}

interface Props {
  column: FormColumn
  modelValue?: number | string | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | null): void
}>()

// Injeta formData do formulário pai (se disponível)
const formData = inject<any>('formData', ref({}))

// Configurações de cálculo
const { calculateFieldValue } = useFieldCalculations(formData)
const calculatedValue = props.column.calculation 
  ? calculateFieldValue(props.column.calculation)
  : computed(() => null)

const hasError = computed(() => !!props.error)

const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  return [{ message: props.error }]
})

const internalValue = computed({
  get: () => {
    // Se tem cálculo, usa o valor calculado
    if (props.column.calculation && calculatedValue.value !== null) {
      return calculatedValue.value
    }
    
    if (props.modelValue !== null && props.modelValue !== undefined) {
      return props.modelValue
    }
    return props.column.default || null
  },
  set: (value) => {
    updateValue(value)
  }
})

// Atualiza quando o valor calculado muda
watch(calculatedValue, (newValue) => {
  if (props.column.calculation && newValue !== null) {
    emit('update:modelValue', newValue)
  }
}, { immediate: true })

const updateValue = (value: number | string | null) => {
  // Se tem cálculo, não permite edição manual
  if (props.column.calculation) {
    return
  }
  
  const numValue = value !== null && value !== '' ? Number(value) : null
  emit('update:modelValue', numValue)
}

onMounted(() => {
  if (props.modelValue === null && props.column.default !== undefined) {
    emit('update:modelValue', props.column.default)
  }
})
</script>
