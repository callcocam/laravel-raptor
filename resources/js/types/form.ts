/**
 * Tipos compartilhados para componentes de formulário
 */

/**
 * Configuração base de uma coluna/campo de formulário
 */
export interface FormColumn {
  name: string
  label?: string
  type?: string
  component?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  helpText?: string
  hint?: string | any[] // Pode ser string ou array de actions
  tooltip?: string
  default?: any
  columnSpan?: string
  gridColumns?: string
  order?: number
  gap?: string
  responsive?: {
    grid?: { sm?: string; md?: string; lg?: string; xl?: string }
    span?: { sm?: string; md?: string; lg?: string; xl?: string }
  }
  prepend?: string
  append?: string
  prefix?: string
  suffix?: string
  icon?: string
  [key: string]: any
}

/**
 * Props base para campos de formulário
 */
export interface FormFieldProps {
  column: FormColumn
  modelValue?: any
  error?: string | string[]
}

// --- Contrato de emissão: como os campos passam dados para o FormRenderer ---

/**
 * Atualização de múltiplos campos do form em um único emit.
 * Use createMultiFieldUpdate() para construir.
 * O FormRenderer aplica cada chave em formData[key] = value.
 */
export interface MultiFieldUpdate {
  readonly __raptorMulti: true
  fields: Record<string, any>
}

/**
 * Valor que atualiza apenas o campo da coluna (column.name).
 * Pode ser primitivo, File, FileList, array, ou objeto (ex: select que retorna { id, label }).
 */
export type SingleFieldValue = string | number | boolean | null | File | FileList | Blob | any[] | Record<string, any>

/**
 * Contrato: cada campo emite EITHER um valor para o seu nome OU uma atualização multi-campo.
 * - SingleFieldValue → FormRenderer faz formData[column.name] = value
 * - MultiFieldUpdate → FormRenderer faz formData[key] = value para cada key em fields
 */
export type FieldEmitValue = SingleFieldValue | MultiFieldUpdate

/** Marca explícita para não confundir objeto-valor (ex: select) com multi-field */
export const RAPTOR_MULTI_MARKER = '__raptorMulti' as const

/**
 * Cria um payload para atualizar vários campos do form de uma vez.
 * Use em campos como CNPJ, CEP, Cascading, Section que preenchem múltiplos inputs.
 */
export function createMultiFieldUpdate(fields: Record<string, any>): MultiFieldUpdate {
  return { __raptorMulti: true, fields }
}

/**
 * Type guard: retorna true se o valor é uma atualização multi-campo.
 */
export function isMultiFieldUpdate(value: FieldEmitValue): value is MultiFieldUpdate {
  return (
    typeof value === 'object' &&
    value !== null &&
    (value as MultiFieldUpdate).__raptorMulti === true &&
    typeof (value as MultiFieldUpdate).fields === 'object'
  )
}
