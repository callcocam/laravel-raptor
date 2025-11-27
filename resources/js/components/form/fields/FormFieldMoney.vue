<!--
 * FormFieldMoney - Money input field with currency formatting
 *
 * Supports multiple currencies with automatic formatting
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <!-- Input with currency prefix -->
    <div class="flex rounded-md shadow-sm">
      <div
        class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-input bg-muted text-muted-foreground text-sm font-medium"
      >
        {{ currencySymbol }}
      </div>

      <Input
        :id="column.name"
        :name="column.name"
        type="text"
        inputmode="decimal"
        :placeholder="formattedPlaceholder"
        :required="column.required"
        :disabled="column.disabled"
        :readonly="column.readonly"
        :modelValue="displayValue"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        :aria-invalid="hasError"
        :class="[hasError ? 'border-destructive' : '', 'rounded-l-none']"
      />
    </div>

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed, ref, watch, inject } from 'vue'
import { Input } from '@/components/ui/input'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import { useFieldCalculations, type FieldCalculation } from '~/composables/useFieldCalculations'

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  tooltip?: string
  helpText?: string
  hint?: string
  currency?: string
  locale?: string
  decimals?: number
  decimalSeparator?: string
  thousandsSeparator?: string
  calculation?: FieldCalculation
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
  (e: 'update:modelValue', value: string | null): void
}>()

// Injeta formData do formulário pai (se disponível)
const formData = inject<any>('formData', ref({}))

const displayValue = ref('')
const cursorPosition = ref(0)

// Configurações de cálculo
const { calculateFieldValue } = useFieldCalculations(formData)
const calculatedValue = props.column.calculation 
  ? calculateFieldValue(props.column.calculation)
  : computed(() => null)

// Configurações de moeda
const currency = computed(() => props.column.currency || 'BRL')
const decimals = computed(() => props.column.decimals ?? 2)
const decimalSeparator = computed(() => props.column.decimalSeparator || ',')
const thousandsSeparator = computed(() => props.column.thousandsSeparator || '.')

// Símbolos de moeda
const currencySymbols: Record<string, string> = {
  BRL: 'R$',
  USD: '$',
  EUR: '€',
  GBP: '£',
  JPY: '¥',
  ARS: '$',
  CLP: '$',
  MXN: '$',
  PEN: 'S/',
  UYU: '$U',
}

const currencySymbol = computed(() => currencySymbols[currency.value] || currency.value)

const hasError = computed(() => !!props.error)

const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  return [{ message: props.error }]
})

const formattedPlaceholder = computed(() => {
  if (props.column.placeholder) {
    return props.column.placeholder
  }
  return formatMoney(0)
})

// Formata um número para o formato de moeda
const formatMoney = (value: number | null): string => {
  if (value === null || isNaN(value)) {
    return ''
  }

  const parts = value.toFixed(decimals.value).split('.')
  const integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator.value)
  const decimalPart = parts[1] || '0'.repeat(decimals.value)

  return `${integerPart}${decimalSeparator.value}${decimalPart}`
}

// Converte string formatada para número
const parseMoneyToFloat = (value: string): number | null => {
  if (!value || value === '') {
    return null
  }

  // Remove separadores de milhares
  let normalized = value.replace(new RegExp(`\\${thousandsSeparator.value}`, 'g'), '')
  
  // Substitui separador decimal por ponto
  normalized = normalized.replace(new RegExp(`\\${decimalSeparator.value}`, 'g'), '.')
  
  // Remove qualquer caractere que não seja número, ponto ou sinal negativo
  normalized = normalized.replace(/[^\d.\-]/g, '')

  const parsed = parseFloat(normalized)
  
  return isNaN(parsed) ? null : parsed
}

// Atualiza o displayValue quando o modelValue muda
watch(() => props.modelValue, (newValue) => {
  // Se tem cálculo, ignora o modelValue e usa o calculado
  if (props.column.calculation && calculatedValue.value !== null) {
    return
  }
  
  if (newValue === null || newValue === undefined || newValue === '') {
    displayValue.value = ''
  } else {
    const numValue = typeof newValue === 'string' ? parseMoneyToFloat(newValue) : newValue
    displayValue.value = numValue !== null ? formatMoney(numValue) : ''
  }
}, { immediate: true })

// Atualiza quando o valor calculado muda
watch(calculatedValue, (newValue) => {
  if (props.column.calculation && newValue !== null) {
    const formatted = formatMoney(newValue)
    displayValue.value = formatted
    emit('update:modelValue', formatted)
  }
}, { immediate: true })

// Handler de input - formata em tempo real
const handleInput = (event: Event) => {
  // Se tem cálculo, não permite edição manual
  if (props.column.calculation) {
    return
  }
  
  const target = event.target as HTMLInputElement
  const inputValue = target.value
  
  // Remove tudo exceto números
  const numbersOnly = inputValue.replace(/\D/g, '')
  
  if (!numbersOnly) {
    displayValue.value = ''
    emit('update:modelValue', null)
    return
  }
  
  // Converte para número considerando as casas decimais
  const numValue = parseInt(numbersOnly) / Math.pow(10, decimals.value)
  
  // Formata o valor
  const formatted = formatMoney(numValue)
  displayValue.value = formatted
  
  // Emite o valor
  emit('update:modelValue', formatted)
  
  // Posiciona o cursor sempre no final
  setTimeout(() => {
    const endPosition = formatted.length
    target.setSelectionRange(endPosition, endPosition)
  }, 0)
}

// Handler de blur - garante formatação final
const handleBlur = () => {
  if (!displayValue.value) {
    emit('update:modelValue', null)
    return
  }
  
  // Apenas emite o valor já formatado
  emit('update:modelValue', displayValue.value)
}

// Handler de focus - seleciona tudo para facilitar edição
const handleFocus = (event: Event) => {
  const target = event.target as HTMLInputElement
  // Opcional: selecionar todo o texto ao focar
  // target.select()
}
</script>
