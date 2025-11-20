<!--
 * FormFieldNumber - Number input field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnNumber with improved accessibility
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <!-- Input with prepend/append addons -->
    <div v-if="hasPrependOrAppend" class="flex rounded-md shadow-sm">
      <div
        v-if="column.prepend || column.prefix"
        class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-input bg-muted text-muted-foreground text-sm"
      >
        <component
          v-if="prependIcon"
          :is="prependIcon"
          class="h-4 w-4"
        />
        <span v-else>{{ column.prepend || column.prefix }}</span>
      </div>

      <Input
        :id="column.name"
        :name="column.name"
        type="number"
        :placeholder="column.placeholder || column.label"
        :required="column.required"
        :disabled="column.disabled"
        :min="column.min"
        :max="column.max"
        :step="column.step || 1"
        :modelValue="internalValue || undefined"
        @update:modelValue="updateValue"
        :aria-invalid="hasError"
        :class="[hasError ? 'border-destructive' : '', inputClass]"
      />

      <div
        v-if="column.append || column.suffix"
        class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-input bg-muted text-muted-foreground text-sm"
      >
        <component
          v-if="appendIcon"
          :is="appendIcon"
          class="h-4 w-4"
        />
        <span v-else>{{ column.append || column.suffix }}</span>
      </div>
    </div>

    <!-- Input without addons -->
    <Input
      v-else
      :id="column.name"
      :name="column.name"
      type="number"
      :placeholder="column.placeholder || column.label"
      :required="column.required"
      :disabled="column.disabled"
      :min="column.min"
      :max="column.max"
      :step="column.step || 1"
      :modelValue="internalValue || undefined"
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
import { computed, h, onMounted } from 'vue'
import { Input } from '@/components/ui/input'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import * as LucideIcons from 'lucide-vue-next'

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  min?: number
  max?: number
  step?: number
  tooltip?: string
  helpText?: string
  hint?: string
  default?: number
  prepend?: string
  append?: string
  prefix?: string
  suffix?: string
}

interface Props {
  column: FormColumn
  modelValue?: number | string | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | null): void
}>()

const hasError = computed(() => !!props.error)

const hasPrependOrAppend = computed(() => {
  return !!(props.column.prepend || props.column.append || props.column.prefix || props.column.suffix)
})

const inputClass = computed(() => {
  const classes = []
  if (props.column.prepend || props.column.prefix) {
    classes.push('rounded-l-none')
  }
  if (props.column.append || props.column.suffix) {
    classes.push('rounded-r-none')
  }
  return classes.join(' ')
})

const prependIcon = computed(() => {
  if (!props.column.prepend) return null
  const IconComponent = (LucideIcons as any)[props.column.prepend]
  return IconComponent ? h(IconComponent) : null
})

const appendIcon = computed(() => {
  if (!props.column.append) return null
  const IconComponent = (LucideIcons as any)[props.column.append]
  return IconComponent ? h(IconComponent) : null
})

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
    updateValue(value)
  }
})

const updateValue = (value: number | string | null) => {
  const numValue = value !== null && value !== '' ? Number(value) : null
  emit('update:modelValue', numValue)
}

onMounted(() => {
  if (props.modelValue === null && props.column.default !== undefined) {
    emit('update:modelValue', props.column.default)
  }
})
</script>
