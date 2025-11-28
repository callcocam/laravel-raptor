<!--
 * FormFieldSelect - Select input field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnSelect with improved accessibility
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <Select v-model="internalValue" :required="column.required" :disabled="column.disabled">
      <SelectTrigger  class="h-9 w-full" :class="hasError ? 'border-destructive' : ''" :aria-invalid="hasError">
        <SelectValue :placeholder="column.placeholder || 'Selecione...'" />
      </SelectTrigger>
      <SelectContent class="w-full">
        <SelectItem
          v-for="option in options"
          :key="getOptionValue(option)"
          :value="getOptionValue(option)"
        >
          {{ getOptionLabel(option) }}
        </SelectItem>
      </SelectContent>
    </Select>

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useAutoComplete } from '../../../composables/useAutoComplete'

interface SelectOption {
  label?: string
  value?: string | number
  data?: Record<string, any>
  [key: string]: any
}

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  options?: SelectOption[] | Record<string, string>
  optionsData?: Record<string, any>
  tooltip?: string
  helpText?: string
  hint?: string
  autoComplete?: {
    enabled: boolean
    fields: Array<{ source: string, target: string }>
    optionValueKey: string | null
    optionLabelKey: string | null
    returnFullObject: boolean
  }
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

const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  return [{ message: props.error }]
})

const options = computed(() => { 
  if (!props.column.options) return []

  // Comportamento padrão - normaliza options para formato consistente
  if (!Array.isArray(props.column.options)) {
    return Object.entries(props.column.options).map(([value, label]) => ({
      value,
      label,
    }))
  }

  return props.column.options
})

// Computed para optionsData
const optionsData = computed(() => {
  const data = props.column.optionsData || {}
  // Garantir que seja um objeto, não um array
  return Array.isArray(data) ? {} : data
})

// Configura autoComplete se habilitado
useAutoComplete(props.column.name, props.column.autoComplete, optionsData)

const getOptionValue = (option: SelectOption | string): string => {
  if (typeof option === 'string') return option
  return String(option.value ?? option.label ?? '')
}

const getOptionLabel = (option: SelectOption | string): string => {
  if (typeof option === 'string') return option
  return option.label ?? String(option.value) ?? ''
}

const internalValue = computed({
  get: () => props.modelValue ? String(props.modelValue) : undefined,
  set: (value) => {
    emit('update:modelValue', value || null)
  },
})
</script>
