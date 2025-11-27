/**
 * Composable para gerenciar autoComplete de campos Select/Combobox
 * 
 * Preenche automaticamente outros campos do formulário quando uma opção é selecionada
 */

import { inject, watch, type Ref } from 'vue'

interface AutoCompleteField {
  source: string | number | boolean | null
  target: string
  isFixedValue?: boolean
}

interface AutoCompleteConfig {
  enabled: boolean
  fields: AutoCompleteField[]
  optionValueKey: string | null
  optionLabelKey: string | null
  returnFullObject: boolean
}

export function useAutoComplete(
  fieldName: string,
  autoComplete: AutoCompleteConfig | undefined,
  optionsData: Ref<Record<string, any>>
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

      // Busca os dados da opção selecionada em optionsData
      const selectedData = optionsData.value[newValue]
      
      if (!selectedData) {
        return
      }

      // Preenche os campos relacionados
      autoComplete.fields.forEach((field) => {
        let sourceValue
        
        // Se é um valor fixo, usa diretamente
        if (field.isFixedValue) {
          sourceValue = field.source
        } else {
          // Se é um campo, busca no selectedData
          sourceValue = selectedData[field.source as string]
        }
        
        if (sourceValue !== undefined && sourceValue !== null) {
          // Atualiza o campo target no formData
          formData.value[field.target] = sourceValue
        }
      })
    }
  )
}
