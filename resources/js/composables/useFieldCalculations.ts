/**
 * Composable para gerenciar cálculos em campos de formulário
 * 
 * Suporta operações matemáticas automáticas entre campos do formulário
 * como soma, subtração, multiplicação, divisão, média, porcentagem, etc.
 * 
 * @example Uso no PHP (MoneyField, NumberField, etc)
 * ```php
 * // Soma de campos
 * MoneyField::make('total')
 *   ->sum(['subtotal', 'shipping', 'tax'])
 * 
 * // Subtração
 * MoneyField::make('balance')
 *   ->subtract('total', ['discount', 'credit'])
 * 
 * // Multiplicação
 * MoneyField::make('total_price')
 *   ->multiply(['unit_price', 'quantity'])
 * 
 * // Divisão
 * MoneyField::make('unit_price')
 *   ->divide('total_price', 'quantity')
 * 
 * // Média
 * MoneyField::make('average_price')
 *   ->average(['price1', 'price2', 'price3'])
 * 
 * // Porcentagem
 * MoneyField::make('discount_amount')
 *   ->percentage('subtotal', 10) // 10% de desconto
 *   // ou
 *   ->percentage('subtotal', 'discount_percentage') // % dinâmica
 * 
 * // Cálculo customizado
 * MoneyField::make('grand_total')
 *   ->calculate('subtotal + shipping - discount + (subtotal * tax / 100)')
 * ```
 */

import { computed, type Ref, type ComputedRef } from 'vue'

export interface FieldCalculation {
  type: 'sum' | 'subtract' | 'multiply' | 'divide' | 'average' | 'min' | 'max' | 'percentage' | 'custom'
  fields?: string[]
  minuend?: string
  subtrahends?: string[]
  dividend?: string
  divisor?: string
  value?: string
  percentage?: number | string
  expression?: string
}

/**
 * Extrai valor numérico de diferentes formatos
 */
function extractNumericValue(value: any): number {
  if (value === null || value === undefined || value === '') {
    return 0
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
    return isNaN(parsed) ? 0 : parsed
  }

  return 0
}

/**
 * Obtém valor de um campo do formData
 */
function getFieldValue(formData: Record<string, any>, fieldName: string): number {
  const value = formData[fieldName]
  return extractNumericValue(value)
}

/**
 * Calcula soma de campos
 */
function calculateSum(formData: Record<string, any>, fields: string[]): number {
  return fields.reduce((sum, field) => {
    return sum + getFieldValue(formData, field)
  }, 0)
}

/**
 * Calcula subtração
 */
function calculateSubtract(
  formData: Record<string, any>,
  minuend: string,
  subtrahends: string[]
): number {
  const minuendValue = getFieldValue(formData, minuend)
  const subtrahendSum = calculateSum(formData, subtrahends)
  return minuendValue - subtrahendSum
}

/**
 * Calcula multiplicação de campos
 */
function calculateMultiply(formData: Record<string, any>, fields: string[]): number {
  return fields.reduce((product, field) => {
    return product * getFieldValue(formData, field)
  }, 1)
}

/**
 * Calcula divisão
 */
function calculateDivide(
  formData: Record<string, any>,
  dividend: string,
  divisor: string
): number {
  const dividendValue = getFieldValue(formData, dividend)
  const divisorValue = getFieldValue(formData, divisor)
  
  if (divisorValue === 0) {
    return 0
  }
  
  return dividendValue / divisorValue
}

/**
 * Calcula média de campos
 */
function calculateAverage(formData: Record<string, any>, fields: string[]): number {
  if (fields.length === 0) {
    return 0
  }
  
  const sum = calculateSum(formData, fields)
  return sum / fields.length
}

/**
 * Encontra valor mínimo entre campos
 */
function calculateMin(formData: Record<string, any>, fields: string[]): number {
  if (fields.length === 0) {
    return 0
  }
  
  const values = fields.map(field => getFieldValue(formData, field))
  return Math.min(...values)
}

/**
 * Encontra valor máximo entre campos
 */
function calculateMax(formData: Record<string, any>, fields: string[]): number {
  if (fields.length === 0) {
    return 0
  }
  
  const values = fields.map(field => getFieldValue(formData, field))
  return Math.max(...values)
}

/**
 * Calcula porcentagem
 */
function calculatePercentage(
  formData: Record<string, any>,
  value: string,
  percentage: number | string
): number {
  const baseValue = getFieldValue(formData, value)
  
  let percentValue: number
  if (typeof percentage === 'string') {
    percentValue = getFieldValue(formData, percentage)
  } else {
    percentValue = percentage
  }
  
  return (baseValue * percentValue) / 100
}

/**
 * Calcula expressão customizada
 */
function calculateCustom(
  formData: Record<string, any>,
  expression: string,
  fields: string[] = []
): number {
  try {
    // Substitui nomes de campos pelos valores
    let evaluableExpression = expression
    
    // Se fields foi fornecido, usa apenas esses
    const fieldsToReplace = fields.length > 0 
      ? fields 
      : Object.keys(formData)
    
    fieldsToReplace.forEach(fieldName => {
      const value = getFieldValue(formData, fieldName)
      // Substitui o nome do campo pelo valor, com word boundaries
      const regex = new RegExp(`\\b${fieldName}\\b`, 'g')
      evaluableExpression = evaluableExpression.replace(regex, String(value))
    })
    
    // Avalia a expressão (cuidado: usar apenas com expressões confiáveis)
    // Em produção, considere usar uma biblioteca de avaliação segura
    const result = new Function('return ' + evaluableExpression)()
    
    return isNaN(result) ? 0 : Number(result)
  } catch (error) {
    console.warn('Erro ao calcular expressão:', expression, error)
    return 0
  }
}

/**
 * Executa um cálculo baseado na configuração
 */
function executeCalculation(
  formData: Record<string, any>,
  calculation: FieldCalculation
): number {
  switch (calculation.type) {
    case 'sum':
      return calculation.fields ? calculateSum(formData, calculation.fields) : 0
    
    case 'subtract':
      return calculation.minuend && calculation.subtrahends
        ? calculateSubtract(formData, calculation.minuend, calculation.subtrahends)
        : 0
    
    case 'multiply':
      return calculation.fields ? calculateMultiply(formData, calculation.fields) : 0
    
    case 'divide':
      return calculation.dividend && calculation.divisor
        ? calculateDivide(formData, calculation.dividend, calculation.divisor)
        : 0
    
    case 'average':
      return calculation.fields ? calculateAverage(formData, calculation.fields) : 0
    
    case 'min':
      return calculation.fields ? calculateMin(formData, calculation.fields) : 0
    
    case 'max':
      return calculation.fields ? calculateMax(formData, calculation.fields) : 0
    
    case 'percentage':
      return calculation.value && calculation.percentage !== undefined
        ? calculatePercentage(formData, calculation.value, calculation.percentage)
        : 0
    
    case 'custom':
      return calculation.expression
        ? calculateCustom(formData, calculation.expression, calculation.fields)
        : 0
    
    default:
      console.warn(`Tipo de cálculo não suportado: ${calculation.type}`)
      return 0
  }
}

/**
 * Hook principal para cálculos de campos
 */
export function useFieldCalculations(
  formData: Ref<Record<string, any>>
) {
  /**
   * Calcula o valor de um campo baseado em sua configuração
   */
  function calculateFieldValue(calculation: FieldCalculation | null | undefined): ComputedRef<number> {
    return computed(() => {
      if (!calculation) {
        return 0
      }
      
      return executeCalculation(formData.value, calculation)
    })
  }

  /**
   * Obtém todos os campos que afetam um cálculo
   */
  function getCalculationDependencies(calculation: FieldCalculation | null | undefined): string[] {
    if (!calculation) {
      return []
    }

    const deps: string[] = []

    if (calculation.fields) {
      deps.push(...calculation.fields)
    }
    
    if (calculation.minuend) {
      deps.push(calculation.minuend)
    }
    
    if (calculation.subtrahends) {
      deps.push(...calculation.subtrahends)
    }
    
    if (calculation.dividend) {
      deps.push(calculation.dividend)
    }
    
    if (calculation.divisor) {
      deps.push(calculation.divisor)
    }
    
    if (calculation.value) {
      deps.push(calculation.value)
    }
    
    if (typeof calculation.percentage === 'string') {
      deps.push(calculation.percentage)
    }

    return [...new Set(deps)] // Remove duplicados
  }

  return {
    calculateFieldValue,
    getCalculationDependencies,
  }
}
