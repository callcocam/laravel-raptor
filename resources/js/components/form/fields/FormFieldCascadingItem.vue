<!--
 * FormFieldCascadingItem - Cascading select field that reloads via Inertia.js
 *
 * When a value is selected, it reloads the page with the new value as a query parameter
 * This allows dependent fields to be populated based on the selected value
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <Select
      v-model="internalValue"
      :required="column.required"
      :disabled="isDisabled"
    >
      <SelectTrigger :class="hasError ? 'border-destructive' : ''" :aria-invalid="hasError">
        <SelectValue :placeholder="column.placeholder || 'Selecione...'" />
      </SelectTrigger>
      <SelectContent>
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
import { computed, inject, watch, type ComputedRef } from 'vue'
import { router } from '@inertiajs/vue3'
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
  dependsOn?: string | null
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

// Inject function to get values of all cascading fields
const getCascadingValues = inject<() => Record<string, any>>('getCascadingValues', () => ({}))

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

  if (!Array.isArray(props.column.options)) {
    return Object.entries(props.column.options).map(([value, label]) => ({
      value,
      label,
    }))
  }

  return props.column.options
})

// Disable field if it depends on another field and that field has no value
const isDisabled = computed(() => {
  if (!props.column.dependsOn) return false

  const cascadingValues = getCascadingValues()
  const dependencyValue = cascadingValues[props.column.dependsOn]

  return !dependencyValue
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
    // Don't trigger reload if value didn't actually change
    if (value === props.modelValue) return

    emit('update:modelValue', value || null)

    // Reload page with new value via Inertia
    reloadWithCascadingValues(value)
  },
})

/**
 * Reloads the page with all cascading field values as query parameters
 */
const reloadWithCascadingValues = (newValue: string | number | null | undefined) => {
  const cascadingValues = getCascadingValues()

  // Build query params with all cascading values
  const params = { ...cascadingValues }

  // Update the current field value
  if (newValue) {
    params[props.column.name] = newValue
  } else {
    delete params[props.column.name]
  }

  // Remove values from fields that depend on this one
  // This ensures child fields are cleared when parent changes
  clearDependentFieldValues(params)

  // Reload page with Inertia, preserving state and scroll position
  router.get(window.location.pathname, params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['form'] // Only reload form data, not everything
  })
}

/**
 * Clears values of fields that depend on the current field
 * This is called when the current field value changes
 */
const clearDependentFieldValues = (params: Record<string, any>) => {
  // Get all cascading fields from inject (it's a ComputedRef)
  const allFieldsRef = inject<ComputedRef<FormColumn[]>>('cascadingFields')

  // If not provided, return early
  if (!allFieldsRef) {
    return
  }

  // Get the actual array value from the computed ref
  const allFields = allFieldsRef.value

  // Safety check - ensure allFields is an array
  if (!allFields || !Array.isArray(allFields)) {
    return
  }

  // Find fields that depend on this field
  allFields.forEach(field => {
    if (field.dependsOn === props.column.name) {
      delete params[field.name]

      // Recursively clear fields that depend on the dependent field
      clearDependentFieldValuesRecursive(params, field.name, allFields)
    }
  })
}

/**
 * Recursively clears all descendant field values
 */
const clearDependentFieldValuesRecursive = (
  params: Record<string, any>,
  fieldName: string,
  allFields: FormColumn[]
) => {
  allFields.forEach(field => {
    if (field.dependsOn === fieldName) {
      delete params[field.name]
      clearDependentFieldValuesRecursive(params, field.name, allFields)
    }
  })
}

// Watch for changes in dependency field - if it changes, clear this field
watch(
  () => {
    if (!props.column.dependsOn) return null
    const cascadingValues = getCascadingValues()
    return cascadingValues[props.column.dependsOn]
  },
  (newDependencyValue, oldDependencyValue) => {
    // If dependency changed and this field has a value, clear it
    if (newDependencyValue !== oldDependencyValue && props.modelValue) {
      emit('update:modelValue', null)
    }
  }
)
</script>
