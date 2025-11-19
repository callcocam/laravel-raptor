<!--
 * FormColumnSelect - Componente de campo select
 *
 * Campo select/dropdown para seleção de opções
 -->
<template>
  <div class="space-y-2">
    <Label v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </Label>

    <Select v-model="internalValue" :required="column.required">
      <SelectTrigger :class="hasError ? 'border-destructive' : ''">
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

    <!-- Mensagem de erro -->
    <p v-if="errorMessage" class="text-sm text-destructive">
      {{ errorMessage }}
    </p>

    <!-- Dica/tooltip -->
    <p v-if="column.tooltip && !errorMessage" class="text-xs text-muted-foreground">
      {{ column.tooltip }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Label } from '@/components/ui/label'
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
  options?: SelectOption[] | Record<string, string>
  tooltip?: string
}

interface Props {
  column: FormColumn
  modelValue?: string | number | null
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const errorMessage = ref('')
const hasError = computed(() => !!errorMessage.value)

// Normaliza as opções
const options = computed(() => {
  if (!props.column.options) return []

  // Se for um objeto, converte para array
  if (!Array.isArray(props.column.options)) {
    return Object.entries(props.column.options).map(([value, label]) => ({
      value,
      label,
    }))
  }

  return props.column.options
})

// Obtém o valor de uma opção
const getOptionValue = (option: SelectOption | string): string => {
  if (typeof option === 'string') return option
  return String(option.value ?? option.label ?? '')
}

// Obtém o label de uma opção
const getOptionLabel = (option: SelectOption | string): string => {
  if (typeof option === 'string') return option
  return option.label ?? String(option.value) ?? ''
}

const internalValue = computed({
  get: () => props.modelValue ? String(props.modelValue) : undefined,
  set: (value) => {
    errorMessage.value = ''
    emit('update:modelValue', value || null)
  },
})
</script>
