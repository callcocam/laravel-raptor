<!--
 * FormFieldCheckbox - Checkbox field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnCheckbox with improved accessibility
 -->
<template>
  <Field orientation="horizontal" :data-invalid="hasError" class="gap-y-1">
    <Checkbox
      :id="column.name"
      :name="column.name"
      :required="column.required"
      :checked="internalValue"
      @update:checked="updateValue"
      :aria-invalid="hasError"
    />

    <div class="space-y-1">
      <FieldLabel :for="column.name">
        {{ column.label }}
        <span v-if="column.required" class="text-destructive">*</span>
      </FieldLabel>

      <FieldDescription v-if="column.description || column.helpText || column.hint || column.tooltip">
        {{ column.description || column.helpText || column.hint || column.tooltip }}
      </FieldDescription>

      <FieldError :errors="errorArray" />
    </div>
  </Field>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import { Checkbox } from '@/components/ui/checkbox'

interface FormColumn {
  name: string
  label?: string
  required?: boolean
  description?: string
  tooltip?: string
  helpText?: string
  hint?: string
  default?: boolean
}

interface Props {
  column: FormColumn
  modelValue?: boolean | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

const hasError = computed(() => !!props.error)

const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  return [{ message: props.error }]
})

const internalValue = computed(() => {
  if (props.modelValue !== null) {
    return props.modelValue
  }
  return props.column.default || false
})

const updateValue = (value: boolean) => {
  emit('update:modelValue', value)
}
</script>
