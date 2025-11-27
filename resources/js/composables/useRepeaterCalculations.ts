/**
 * Composable para gerenciar cálculos em campos Repeater
 * 
 * Suporta operações matemáticas automáticas como soma, média, mínimo, máximo e contagem
 * aplicadas aos itens de um campo repeater, atualizando campos de resultado especificados.
 * 
 * @example Uso no PHP (RepeaterField)
 * ```php
 * RepeaterField::make('items')
 *   ->sum('total_price', ['total_amount'])
 *   ->sum('quantity', ['total_quantity'])
 *   ->avg('unit_price', ['average_price'])
 *   ->max('unit_price', ['highest_price'])
 *   ->min('unit_price', ['lowest_price'])
 *   ->count('product_name', ['total_items'])
 *   ->fields([
 *     TextField::make('product_name'),
 *     MoneyField::make('unit_price'),
 *     NumberField::make('quantity'),
 *     MoneyField::make('total_price')->readonly(),
 *   ])
 * 
 * // Adicionar campos para exibir os resultados
 * NumberField::make('total_quantity')->readonly(),
 * MoneyField::make('total_amount')->readonly(),
 * MoneyField::make('average_price')->readonly(),
 * ```
 * 
 * @example Uso no Vue (FormFieldRepeater)
 * O composable é usado automaticamente pelo componente FormFieldRepeater.vue
 * quando o campo possui a propriedade `calculations` definida.
 */

import { computed, type Ref } from 'vue'

export interface Calculation {
  type: 'sum' | 'avg' | 'min' | 'max' | 'count' | string
  sourceField: string
  targetFields: string[]
  options?: Record<string, any>
}

export interface CalculationResults {
  [key: string]: number | null
}

/**
 * Extrai valor numérico de diferentes formatos
 */
function extractNumericValue(value: any): number | null {
  if (value === null || value === undefined || value === '') {
    return null
  }

  // Se já é número
  if (typeof value === 'number') {
    return value
  }

  // Se é string, tenta converter
  if (typeof value === 'string') {
    // Remove formatação de moeda e separadores
    const cleaned = value
      .replace(/[^\d.,-]/g, '') // Remove tudo exceto números, ponto, vírgula e hífen
      .replace(/\./g, '') // Remove pontos (separador de milhar no BR)
      .replace(',', '.') // Converte vírgula em ponto (decimal)

    const parsed = parseFloat(cleaned)
    return isNaN(parsed) ? null : parsed
  }

  return null
}

/**
 * Calcula soma dos valores
 */
function calculateSum(values: (number | null)[]): number {
  return values.reduce((acc: number, val) => {
    return acc + (val ?? 0)
  }, 0)
}

/**
 * Calcula média dos valores
 */
function calculateAvg(values: (number | null)[]): number | null {
  const validValues = values.filter(v => v !== null) as number[]
  
  if (validValues.length === 0) {
    return null
  }

  const sum = calculateSum(validValues)
  return sum / validValues.length
}

/**
 * Encontra valor mínimo
 */
function calculateMin(values: (number | null)[]): number | null {
  const validValues = values.filter(v => v !== null) as number[]
  
  if (validValues.length === 0) {
    return null
  }

  return Math.min(...validValues)
}

/**
 * Encontra valor máximo
 */
function calculateMax(values: (number | null)[]): number | null {
  const validValues = values.filter(v => v !== null) as number[]
  
  if (validValues.length === 0) {
    return null
  }

  return Math.max(...validValues)
}

/**
 * Conta valores não nulos
 */
function calculateCount(values: (number | null)[]): number {
  return values.filter(v => v !== null).length
}

/**
 * Executa um cálculo específico
 */
function executeCalculation(
  type: string,
  values: (number | null)[],
  options?: Record<string, any>
): number | null {
  switch (type) {
    case 'sum':
      return calculateSum(values)
    
    case 'avg':
      return calculateAvg(values)
    
    case 'min':
      return calculateMin(values)
    
    case 'max':
      return calculateMax(values)
    
    case 'count':
      return calculateCount(values)
    
    default:
      console.warn(`Tipo de cálculo não suportado: ${type}`)
      return null
  }
}

/**
 * Hook principal para cálculos do repeater
 */
export function useRepeaterCalculations(
  items: Ref<any[]>,
  calculations: Calculation[] = []
) {
  /**
   * Resultados dos cálculos computados
   */
  const calculationResults = computed<CalculationResults>(() => {
    const results: CalculationResults = {}

    if (!calculations || calculations.length === 0) {
      return results
    }

    calculations.forEach(calc => {
      // Extrai valores do campo fonte de todos os itens
      const values = items.value.map(item => {
        const rawValue = item[calc.sourceField]
        return extractNumericValue(rawValue)
      })

      // Executa o cálculo
      const result = executeCalculation(calc.type, values, calc.options)

      // Armazena o resultado para cada campo de destino
      calc.targetFields.forEach(targetField => {
        results[targetField] = result
      })
    })

    return results
  })

  /**
   * Obtém o valor calculado para um campo específico
   */
  function getCalculatedValue(fieldName: string): number | null {
    return calculationResults.value[fieldName] ?? null
  }

  /**
   * Verifica se um campo é um campo de resultado de cálculo
   */
  function isCalculatedField(fieldName: string): boolean {
    return fieldName in calculationResults.value
  }

  /**
   * Formata valor para exibição (opcional)
   */
  function formatCalculatedValue(
    fieldName: string,
    format: 'currency' | 'number' | 'decimal' = 'number',
    locale: string = 'pt-BR'
  ): string {
    const value = getCalculatedValue(fieldName)

    if (value === null) {
      return '0'
    }

    switch (format) {
      case 'currency':
        return new Intl.NumberFormat(locale, {
          style: 'currency',
          currency: 'BRL'
        }).format(value)

      case 'decimal':
        return new Intl.NumberFormat(locale, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }).format(value)

      case 'number':
      default:
        return new Intl.NumberFormat(locale).format(value)
    }
  }

  return {
    calculationResults,
    getCalculatedValue,
    isCalculatedField,
    formatCalculatedValue,
  }
}
