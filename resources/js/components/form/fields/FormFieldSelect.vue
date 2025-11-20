<!--
 * FormFieldSelect - Select input field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnSelect with improved accessibility
 -->
<template>
  <Field orientation="responsive" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <Select v-model="internalValue" :required="column.required" class="py-1">
      <SelectTrigger :class="hasError ? 'border-destructive w-full' : ' w-full'" :aria-invalid="hasError"  class="w-full">
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

interface SelectOption {
  label?: string
  value?: string | number
  [key: string]: any
}

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  options?: SelectOption[] | Record<string, string>
  tooltip?: string
  helpText?: string
  hint?: string
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
  console.log(props.column)
  if (!props.column.options) return []

  if (!Array.isArray(props.column.options)) {
    return Object.entries(props.column.options).map(([value, label]) => ({
      value,
      label,
    }))
  }

  return props.column.options
})

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
