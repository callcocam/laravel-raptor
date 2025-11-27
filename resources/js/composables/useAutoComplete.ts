/**
 * Composable para gerenciar autoComplete de campos Select/Combobox
 * 
 * Preenche automaticamente outros campos do formulário quando uma opção é selecionada
 */

import { inject, watch, type Ref } from 'vue'

interface AutoCompleteField {
  source: string
  target: string
}

interface AutoCompleteConfig {
  enabled: boolean
  fields: AutoCompleteField[]
  optionValueKey: string | null
  optionLabelKey: string | null
  returnFullObject: boolean
}

interface SelectOption {
  value: string | number
  label: string
  data?: Record<string, any>
}

export function useAutoComplete(
  fieldName: string,
  autoComplete: AutoCompleteConfig | undefined,
  options: Ref<SelectOption[] | Record<string, string>[]>
) {
  // Injeta formData do contexto
  const formData = inject<Ref<Record<string, any>>>('formData')

  // Se não tem autoComplete configurado, não faz nada
  if (!autoComplete || !autoComplete.enabled || !formData) {
    return
  }

  // Observa mudanças no valor do campo
  watch(
    () => formData.value[fieldName],
    (newValue) => {
      if (!newValue || !autoComplete.fields || autoComplete.fields.length === 0) {
        return
      }

      // Encontra a opção selecionada
      const selectedOption = findSelectedOption(newValue, options.value)
      
      if (!selectedOption || !selectedOption.data) {
        return
      }

      // Preenche os campos relacionados
      autoComplete.fields.forEach((field) => {
        const sourceValue = selectedOption.data?.[field.source]
        
        if (sourceValue !== undefined && sourceValue !== null) {
          // Atualiza o campo target no formData
          formData.value[field.target] = sourceValue
        }
      })
    }
  )
}

/**
 * Encontra a opção selecionada nas options
 */
function findSelectedOption(
  value: any,
  options: SelectOption[] | Record<string, string>[]
): SelectOption | null {
  if (!Array.isArray(options)) {
    return null
  }

  // Procura nas opções processadas
  for (const option of options) {
    if ('value' in option && option.value === value) {
      return option as SelectOption
    }
  }

  return null
}

/**
 * Normaliza opções para o formato esperado pelo autoComplete
 * 
 * @param options Opções originais (podem ser array de objetos ou key-value)
 * @param valueKey Campo a ser usado como valor
 * @param labelKey Campo a ser usado como label
 */
export function normalizeOptions(
  options: any,
  valueKey: string | null = null,
  labelKey: string | null = null
): SelectOption[] {
  if (!options) {
    return []
  }

  // Se já está no formato correto (array com value, label, data)
  if (Array.isArray(options) && options.length > 0 && 'value' in options[0]) {
    return options as SelectOption[]
  }

  // Se é um objeto simples { key: value }
  if (!Array.isArray(options) && typeof options === 'object') {
    return Object.entries(options).map(([key, value]) => ({
      value: key,
      label: String(value),
      data: undefined,
    }))
  }

  // Se é um array de objetos
  if (Array.isArray(options)) {
    return options.map((item) => {
      if (typeof item === 'object' && item !== null) {
        return {
          value: valueKey ? item[valueKey] : (item.id ?? item.value),
          label: labelKey ? item[labelKey] : (item.name ?? item.label ?? String(item)),
          data: item,
        }
      }
      
      return {
        value: item,
        label: String(item),
        data: undefined,
      }
    })
  }

  return []
}
