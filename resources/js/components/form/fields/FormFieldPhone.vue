<!--
 * FormFieldText - Text input field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnText with improved accessibility
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
        :type="column.type || 'text'"
        :placeholder="column.placeholder || column.label"
        :required="column.required"
        :disabled="column.disabled"
        :readonly="column.readonly"
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
import { computed, onMounted } from 'vue'
import { Input } from '@/components/ui/input'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import AddonsContext from '../AddonsContext.vue'

interface FormColumn {
  name: string
  label?: string
  type?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  tooltip?: string
  helpText?: string
  hint?: string
  default?: string | number
  prepend?: string
  append?: string
  prefix?: string
  suffix?: string
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

const internalValue = computed({
  get: () => {
    if (props.modelValue !== null && props.modelValue !== undefined) {
      return props.modelValue
    }
    return props.column.default || null
  },
  set: (value) => {
    emit('update:modelValue', value)
  }
})

const updateValue = (value: string | number | null) => {
  emit('update:modelValue', value)
}

onMounted(() => {
  if (props.modelValue === null && props.column.default) {
    emit('update:modelValue', props.column.default)
  }
})
</script>
