import { computed, type ComputedRef } from 'vue'
import type { TableAction } from '~/types/table'

interface FormColumn {
  name: string
  label?: string
  component?: string
  required?: boolean
  [key: string]: any
}

interface ActionConfigOptions {
  action: TableAction & {
    columns?: FormColumn[]
    gridColumns?: string
    gap?: string
    maxWidth?: string
    color?: string | null
  }
  columns?: ComputedRef<FormColumn[]>
}

/**
 * Composable para gerenciar configurações comuns de actions
 * Usado em ActionModalForm e ActionModalSlideover
 */
export function useActionConfig(options: ActionConfigOptions) {
  const { action, columns } = options

  // Configurações de grid do formulário
  const gridColumns = computed(() => {
    return action.gridColumns || '12'
  })

  const gap = computed(() => {
    return action.gap || '4'
  })

  // Classes do DialogContent (largura e altura)
  const dialogClasses = computed(() => {
    const maxWidthMap: Record<string, string> = {
      'sm': 'sm:max-w-sm',
      'md': 'sm:max-w-md',
      'lg': 'sm:max-w-lg',
      'xl': 'sm:max-w-xl',
      '2xl': 'sm:max-w-2xl',
      '3xl': 'sm:max-w-3xl',
      '4xl': 'sm:max-w-4xl',
      '5xl': 'sm:max-w-5xl',
      '6xl': 'sm:max-w-6xl',
      '7xl': 'sm:max-w-7xl',
      'full': 'sm:max-w-full',
    }

    const maxWidth = action.maxWidth || '4xl'

    return [
      maxWidthMap[maxWidth] || 'max-w-4xl',
      'max-h-[90vh]',
      'overflow-y-auto',
    ].join(' ')
  })

  // Verifica se há colunas
  const hasFormColumns = computed(() => {
    if (columns) {
      return columns.value.length > 0
    }
    return (action.columns || []).length > 0
  })

  // Mapeia cor para variant do shadcn
  const variant = computed(() => {
    const colorMap: Record<string, any> = {
      'green': 'default',
      'blue': 'default',
      'red': 'destructive',
      'yellow': 'warning',
      'gray': 'secondary',
      'default': 'default'
    }

    return colorMap[action.color || 'default'] || 'default'
  })

  return {
    gridColumns,
    gap,
    dialogClasses,
    hasFormColumns,
    variant,
  }
}
