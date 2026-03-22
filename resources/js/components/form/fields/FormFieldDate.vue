<!--
 * FormFieldDate - Date input field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnDate with improved accessibility
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <div class="flex items-center justify-between w-full">
      <FieldLabel v-if="column.label" :for="column.name">
        {{ column.label }}
        <span v-if="column.required" class="text-destructive">*</span>
      </FieldLabel>
      <HintRenderer v-if="column.hint" :hint="column.hint" class="ml-2" />
    </div>

    <!-- Input with conditional addons -->
    <AddonsContext
      :prepend="column.prepend"
      :append="column.append"
      :prefix="column.prefix"
      :suffix="column.suffix"
      :icon="column.icon"
      v-slot="{ inputClass: addonClass }"
    >
      <Input
        :id="column.name"
        :name="column.name"
        :type="column.withTime ? 'datetime-local' : 'date'"
        :required="column.required"
        :disabled="column.disabled"
        :readonly="column.readonly"
        :min="column.minDate"
        :max="column.maxDate"
        :modelValue="internalValue || undefined"
        @update:modelValue="updateValue"
        :aria-invalid="hasError"
        :class="[hasError ? 'border-destructive' : '', addonClass]"
      />
    </AddonsContext>

    <FieldDescription v-if="column.helpText">
      {{ column.helpText }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Field, FieldLabel, FieldDescription, FieldError } from '~/components/ui/field'
import { Input } from '~/components/ui/input'
import AddonsContext from '../AddonsContext.vue'
import HintRenderer from '../HintRenderer.vue'

interface FormColumn {
  name: string
  label?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  minDate?: string
  maxDate?: string
  withTime?: boolean
  tooltip?: string
  helpText?: string
  hint?: string | any[]
  default?: string
  prepend?: string
  append?: string
  prefix?: string
  suffix?: string
  icon?: string
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

const internalValue = computed({
  get: () => {
    if (props.modelValue !== null && props.modelValue !== undefined) {
      return props.modelValue
    }
    return props.column.default || null
  },
  set: (value) => {
    emit('update:modelValue', value)
  },
})

const updateValue = (value: string | number | null) => {
  emit('update:modelValue', value ? String(value) : null)
}

onMounted(() => {
  if (props.modelValue === null && props.column.default) {
    emit('update:modelValue', props.column.default)
  }
})
</script>
