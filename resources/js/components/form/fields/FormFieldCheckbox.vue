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
      :disabled="column.disabled"
      :model-value="internalValue"
      :aria-invalid="hasError"
      @update:model-value="updateValue"
    />

    <div class="space-y-1">
      <div class="flex items-center gap-x-1">
        <FieldLabel :for="column.name">
          {{ column.label }}
          <span v-if="column.required" class="text-destructive">*</span>
        </FieldLabel>
        <HintRenderer v-if="column.hint" :hint="column.hint" />
      </div>

      <FieldDescription v-if="column.description || column.helpText">
        {{ column.description || column.helpText }}
      </FieldDescription>

      <FieldError :errors="errorArray" />
    </div>
  </Field>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Field, FieldLabel, FieldDescription, FieldError } from '~/components/ui/field'
import { Checkbox } from '~/components/ui/checkbox'
import HintRenderer from '../HintRenderer.vue'

interface FormColumn {
  name: string
  label?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  description?: string
  tooltip?: string
  helpText?: string
  hint?: string | any[]
  default?: boolean
}

interface Props {
  column: FormColumn
  modelValue?: boolean | null | undefined | string | number
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
  const raw = props.modelValue !== null && props.modelValue !== undefined
    ? props.modelValue
    : (props.column.default ?? false)
  return toBoolean(raw)
})

function toBoolean(value: boolean | string | number | null | undefined): boolean {
  if (value === true || value === 'true' || value === 1) return true
  if (value === false || value === 'false' || value === 0) return false
  return Boolean(value)
}

const updateValue = (value: boolean | 'indeterminate') => {
  const booleanValue = value === 'indeterminate' ? false : value
  emit('update:modelValue', booleanValue)
}
</script>
