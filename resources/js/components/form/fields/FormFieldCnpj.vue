<!--
 * FormFieldCnpj - CNPJ input field with BrasilAPI lookup
 *
 * Integrates with BrasilAPI to fetch company data automatically
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <!-- Input Group with Button -->
    <div class="flex gap-2">
      <div class="relative flex-1">
        <Input
          :id="column.name"
          :name="column.name"
          type="text"
          :placeholder="column.placeholder || 'XX.XXX.XXX/XXXX-XX'"
          :required="column.required"
          :disabled="column.disabled || isLoading"
          :readonly="column.readonly"
          :modelValue="internalCnpjValue || undefined"
          @update:modelValue="updateValue"
          @blur="handleBlur"
          :aria-invalid="hasError"
          :class="hasError ? 'border-destructive' : ''"
          maxlength="18"
        />
        <div v-if="isLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
          <svg
            class="animate-spin h-4 w-4 text-muted-foreground"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
        </div>
      </div>

      <Button
        type="button"
        variant="outline"
        size="default"
        @click="searchCnpj"
        :disabled="!canSearch || isLoading"
        class="shrink-0"
      >
        <Search class="h-4 w-4 mr-2" /> 
      </Button>
    </div>

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError v-if="searchError" :errors="[{ message: searchError }]" />
    <FieldError v-else :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import { Search } from 'lucide-vue-next'

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
  default?: string | number
  fieldMapping?: Record<string, any>
}

interface Props {
  column: FormColumn
  modelValue?: string | number | null | Record<string, any>
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null | Record<string, any>): void
}>()

const isLoading = ref(false)
const searchError = ref('')
const internalCnpjValue = ref<string>('')

const hasError = computed(() => !!props.error)

// Formata CNPJ com máscara
function formatCnpj(value: string): string {
  const cleaned = value.replace(/\D/g, '')
  if (cleaned.length <= 2) return cleaned
  if (cleaned.length <= 5) return `${cleaned.slice(0, 2)}.${cleaned.slice(2)}`
  if (cleaned.length <= 8) return `${cleaned.slice(0, 2)}.${cleaned.slice(2, 5)}.${cleaned.slice(5)}`
  if (cleaned.length <= 12) return `${cleaned.slice(0, 2)}.${cleaned.slice(2, 5)}.${cleaned.slice(5, 8)}/${cleaned.slice(8)}`
  return `${cleaned.slice(0, 2)}.${cleaned.slice(2, 5)}.${cleaned.slice(5, 8)}/${cleaned.slice(8, 12)}-${cleaned.slice(12, 14)}`
}

// Inicializa o valor interno do CNPJ
watch(
  () => props.modelValue,
  (newValue) => {
    // Normaliza o modelValue para sempre ser um objeto ou string
    if (newValue && typeof newValue === 'object') {
      const cnpjValue = newValue[props.column.name]
      internalCnpjValue.value = cnpjValue ? formatCnpj(String(cnpjValue)) : ''
    } else if (newValue !== null && newValue !== undefined) {
      internalCnpjValue.value = formatCnpj(String(newValue))
    } else {
      internalCnpjValue.value = props.column.default ? formatCnpj(String(props.column.default)) : ''
    }
  },
  { immediate: true }
)

// Pode buscar se tiver 14 dígitos
const canSearch = computed(() => {
  if (!internalCnpjValue.value) return false
  const cleaned = String(internalCnpjValue.value).replace(/\D/g, '')
  return cleaned.length === 14
})

const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  return [{ message: props.error }]
})

const updateValue = (value: string | number | null) => {
  if (value) {
    const formatted = formatCnpj(String(value))
    const cleaned = formatted.replace(/\D/g, '')
    
    // Atualiza o valor interno
    internalCnpjValue.value = formatted
    
    // Normaliza o modelValue para sempre ser um objeto
    const currentValue = typeof props.modelValue === 'object' && props.modelValue !== null ? props.modelValue : {}
    
    const updatedValues = {
      ...currentValue,
      [props.column.name]: cleaned
    }
    
    emit('update:modelValue', updatedValues)
  } else {
    internalCnpjValue.value = ''
    
    const currentValue = typeof props.modelValue === 'object' && props.modelValue !== null ? props.modelValue : {}
    
    const updatedValues = {
      ...currentValue,
      [props.column.name]: null
    }
    
    emit('update:modelValue', updatedValues)
  }
}

const handleBlur = () => {
  searchError.value = ''
}

// Busca CNPJ na BrasilAPI
const searchCnpj = async () => {
  if (!canSearch.value) {
    searchError.value = 'Por favor, insira um CNPJ válido.'
    return
  }

  isLoading.value = true
  searchError.value = ''

  try {
    const plainCnpj = String(internalCnpjValue.value).replace(/\D/g, '')
    const response = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${plainCnpj}`)

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Erro ao buscar o CNPJ.')
    }

    const data = await response.json() 

    // Usa o mapeamento definido no backend (obrigatório)
    const fieldMapping = props.column.fieldMapping

    if (!fieldMapping) {
      console.warn('Field mapping não definido no backend!')
      return
    }
 

    const mappedData: Record<string, any> = {}

    // Mapeia os dados da API para os campos do formulário
    Object.entries(fieldMapping).forEach(([apiField, formField]) => {
      const value = data[apiField] || ''
      mappedData[formField as string] = value
    })

    // Normaliza o modelValue para sempre ser um objeto
    const currentValue = typeof props.modelValue === 'object' && props.modelValue !== null ? props.modelValue : {}
    
    // Emite todos os valores mapeados de uma vez, mantendo o CNPJ
    const updatedValues = {
      ...currentValue,
      [props.column.name]: plainCnpj,
      ...mappedData,
    }
    
    emit('update:modelValue', updatedValues)

  } catch (e: any) {
    searchError.value = e.message || 'Não foi possível consultar o CNPJ.'
  } finally {
    isLoading.value = false
  }
}
</script>
