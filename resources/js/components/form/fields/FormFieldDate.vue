<!--
 * FormFieldDate - Date input field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnDate with improved accessibility
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <Input
      :id="column.name"
      :name="column.name"
      :type="column.withTime ? 'datetime-local' : 'date'"
      :required="column.required"
      :disabled="column.disabled"
      :min="column.minDate"
      :max="column.maxDate"
      :modelValue="modelValue || undefined"
      @update:modelValue="updateValue"
      :aria-invalid="hasError"
      :class="hasError ? 'border-destructive' : ''"
    />

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import { Input } from '@/components/ui/input'

interface FormColumn {
  name: string
  label?: string
  required?: boolean
  disabled?: boolean
  minDate?: string
  maxDate?: string
  withTime?: boolean
  tooltip?: string
  helpText?: string
  hint?: string
}

interface Props {
  column: FormColumn
  modelValue?: string | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

const hasError = computed(() => !!props.error)

const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  return [{ message: props.error }]
})

const updateValue = (value: any) => {
  emit('update:modelValue', value)
}
</script>
