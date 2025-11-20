<!--
 * FormFieldPassword - Password input field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnPassword with improved accessibility
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <div class="relative">
      <Input
        :id="column.name"
        :name="column.name"
        :type="showPassword ? 'text' : 'password'"
        :placeholder="column.placeholder || column.label"
        :required="column.required"
        :disabled="column.disabled"
        :minlength="column.minLength"
        :modelValue="modelValue || undefined"
        @update:modelValue="updateValue"
        :aria-invalid="hasError"
        :class="[hasError ? 'border-destructive' : '', column.showToggle ? 'pr-10' : '']"
      />

      <button
        v-if="column.showToggle !== false"
        type="button"
        @click="showPassword = !showPassword"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
        :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
      >
        <EyeOff v-if="showPassword" class="h-4 w-4" />
        <Eye v-else class="h-4 w-4" />
      </button>
    </div>

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Eye, EyeOff } from 'lucide-vue-next'

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  minLength?: number
  showToggle?: boolean
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

const showPassword = ref(false)
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
