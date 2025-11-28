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
          :modelValue="internalValue || undefined"
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
import { computed, ref } from 'vue'
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

// Pode buscar se tiver 14 dígitos
const canSearch = computed(() => {
  if (!internalValue.value) return false
  const cleaned = String(internalValue.value).replace(/\D/g, '')
  return cleaned.length === 14
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
    // Se modelValue for um objeto, pega o valor do campo CNPJ
    if (props.modelValue && typeof props.modelValue === 'object') {
      const cnpjValue = (props.modelValue as Record<string, any>)[props.column.name]
      return cnpjValue ? formatCnpj(String(cnpjValue)) : null
    }
    // Se for string/number, formata diretamente
    if (props.modelValue !== null && props.modelValue !== undefined) {
      return formatCnpj(String(props.modelValue))
    }
    return props.column.default ? formatCnpj(String(props.column.default)) : null
  },
  set: (value) => {
    if (value) {
      const cleaned = String(value).replace(/\D/g, '')
      // Emite apenas o valor do campo CNPJ
      emit('update:modelValue', cleaned)
    } else {
      emit('update:modelValue', null)
    }
  }
})

const updateValue = (value: string | number | null) => {
  if (value) {
    const formatted = formatCnpj(String(value))
    const cleaned = formatted.replace(/\D/g, '')
    emit('update:modelValue', cleaned)
  } else {
    emit('update:modelValue', null)
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
    const plainCnpj = String(internalValue.value).replace(/\D/g, '')
    const response = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${plainCnpj}`)

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Erro ao buscar o CNPJ.')
    }

    const data = await response.json()
    console.log('BrasilAPI response:', data)

    // Usa o mapeamento definido no backend (obrigatório)
    const fieldMapping = props.column.fieldMapping

    if (!fieldMapping) {
      console.warn('Field mapping não definido no backend!')
      return
    }

    console.log('Field mapping:', fieldMapping)

    const mappedData: Record<string, any> = {}

    // Mapeia os dados da API para os campos do formulário
    Object.entries(fieldMapping).forEach(([apiField, formField]) => {
      const value = data[apiField] || ''
      console.log(`Mapping ${apiField} (${value}) -> ${formField}`)
      
      // Suporta campos aninhados (ex: address.street)
      if (String(formField).includes('.')) {
        const parts = String(formField).split('.')
        const [parent, child] = parts
        
        if (!mappedData[parent]) {
          mappedData[parent] = {}
        }
        mappedData[parent][child] = value
      } else {
        // Campo simples
        mappedData[formField as string] = value
      }
    })

    // Emite todos os valores mapeados de uma vez
    const updatedValues = {
      ...(typeof props.modelValue === 'object' ? props.modelValue : {}),
      ...mappedData,
    }
    console.log('Emitting all CNPJ values:', updatedValues)
    emit('update:modelValue', updatedValues)

  } catch (e: any) {
    searchError.value = e.message || 'Não foi possível consultar o CNPJ.'
  } finally {
    isLoading.value = false
  }
}
</script>
