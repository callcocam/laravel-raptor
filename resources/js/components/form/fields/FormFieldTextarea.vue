<!--
 * FormFieldTextarea - Textarea field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnTextarea with improved accessibility
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <Textarea
      :id="column.name"
      :name="column.name"
      :placeholder="column.placeholder || column.label"
      :required="column.required"
      :disabled="column.disabled"
      :rows="column.rows || 3"
      :maxlength="column.maxLength"
      :modelValue="modelValue || undefined"
      @update:modelValue="updateValue"
      :aria-invalid="hasError"
      :class="hasError ? 'border-destructive' : ''"
    />

    <div v-if="column.maxLength" class="flex justify-between items-center gap-2">
      <FieldDescription v-if="column.helpText || column.hint || column.tooltip" class="flex-1">
        {{ column.helpText || column.hint || column.tooltip }}
      </FieldDescription>
      <span class="text-xs text-muted-foreground">
        {{ charCount }} / {{ column.maxLength }}
      </span>
    </div>

    <FieldDescription v-else-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import { Textarea } from '@/components/ui/textarea/index'

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  rows?: number
  maxLength?: number
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

const charCount = computed(() => {
  return props.modelValue?.length || 0
})

const updateValue = (value: any) => {
  emit('update:modelValue', value)
}
</script>
