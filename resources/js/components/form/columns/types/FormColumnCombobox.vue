<!--
 * FormColumnCombobox - Wrapper for FormFieldCombobox
 *
 * Uses FieldWrapper for consistent layout and error handling
 -->
<template>
  <FieldWrapper :column="column" :error="error">
    <FormFieldCombobox
      :column="column"
      :modelValue="modelValue"
      :error="error"
      @update:modelValue="$emit('update:modelValue', $event)"
    />
  </FieldWrapper>
</template>

<script setup lang="ts">
import FieldWrapper from '../../FieldWrapper.vue'
import FormFieldCombobox from '../../fields/FormFieldCombobox.vue'

interface ComboboxOption {
  label?: string
  value?: string | number
  [key: string]: any
}

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  required?: boolean
  options?: ComboboxOption[] | Record<string, string>
  tooltip?: string
  helpText?: string
  hint?: string
  columnSpan?: string | number
  columnStart?: string | number
}

interface Props {
  column: FormColumn
  modelValue?: string | number | null
  error?: string | string[]
}

withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()
</script>
