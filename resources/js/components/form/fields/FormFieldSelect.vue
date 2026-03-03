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

    <div class="relative">
      <Select v-model="internalValue" :required="column.required" :disabled="column.disabled">
        <SelectTrigger class="h-9 w-full" :class="hasError ? 'border-destructive' : ''" :aria-invalid="hasError">
          <SelectValue :placeholder="column.placeholder || 'Selecione...'" />
        </SelectTrigger>
        <SelectContent class="w-full">
          <SelectItem v-for="option in options" :key="getOptionValue(option)" :value="getOptionValue(option)">
            {{ getOptionLabel(option) }}
          </SelectItem>
        </SelectContent>
      </Select>
      
      <!-- Clear button -->
      <button
        v-if="internalValue"
        type="button"
        @click="clearSelection"
        class="absolute right-10 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors"
        title="Limpar seleção"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
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
  default?: string | number | null
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
  get: () => props.modelValue ? String(props.modelValue) : props.column?.default ?? undefined,
  set: (value) => {
    emit('update:modelValue', value || null)
  },
})

const clearSelection = () => {
  emit('update:modelValue', null)
}

onMounted(() => {
  if (props.modelValue === null && props.column.default) {
    emit('update:modelValue', props.column.default)
  }
})
</script>
