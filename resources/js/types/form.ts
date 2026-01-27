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
