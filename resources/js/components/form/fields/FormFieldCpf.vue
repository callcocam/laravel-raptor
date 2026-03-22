<!--
 * FormFieldCpf - CPF input field with mask
 *
 * Formats CPF as ###.###.###-## and emits the cleaned value
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
        type="text"
        :placeholder="column.placeholder || '###.###.###-##'"
        :required="column.required"
        :disabled="column.disabled"
        :readonly="column.readonly"
        :modelValue="internalValue || undefined"
        @update:modelValue="updateValue"
        :aria-invalid="hasError"
        :class="[hasError ? 'border-destructive' : '', addonClass]"
        maxlength="14"
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
import { Input } from '~/components/ui/input'
import { Field, FieldLabel, FieldDescription, FieldError } from '~/components/ui/field'
import AddonsContext from '../AddonsContext.vue'
import HintRenderer from '../HintRenderer.vue'

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
  hint?: string | any[]
  default?: string | number
  prepend?: string
  append?: string
  prefix?: string
  suffix?: string
  icon?: string
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

function formatCpf(value: string): string {
  const cleaned = value.replace(/\D/g, '')
  if (cleaned.length <= 3) return cleaned
  if (cleaned.length <= 6) return `${cleaned.slice(0, 3)}.${cleaned.slice(3)}`
  if (cleaned.length <= 9) return `${cleaned.slice(0, 3)}.${cleaned.slice(3, 6)}.${cleaned.slice(6)}`
  return `${cleaned.slice(0, 3)}.${cleaned.slice(3, 6)}.${cleaned.slice(6, 9)}-${cleaned.slice(9, 11)}`
}

const internalValue = computed({
  get: () => {
    const raw = props.modelValue !== null && props.modelValue !== undefined
      ? props.modelValue
      : (props.column.default || null)
    return raw ? formatCpf(String(raw)) : null
  },
  set: (value) => {
    emit('update:modelValue', value)
  },
})

const updateValue = (value: string | number | null) => {
  if (value) {
    emit('update:modelValue', formatCpf(String(value)))
  } else {
    emit('update:modelValue', null)
  }
}

onMounted(() => {
  if (props.modelValue === null && props.column.default) {
    emit('update:modelValue', props.column.default)
  }
})
</script>
