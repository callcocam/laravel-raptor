/**
 * useActionUI - Composable para lógica comum de UI em actions
 *
 * Centraliza a lógica repetida em componentes de ações:
 * - Mapeamento de cor para variant
 * - Cálculo de classes de ícone
 * - Carregamento dinâmico de ícones
 * - Configurações de tamanho e variante
 */

import { computed, h, type ComputedRef } from 'vue'
import * as LucideIcons from 'lucide-vue-next'
import type { TableAction } from '~/types/table'

interface UseActionUIOptions {
  action: TableAction
  defaultSize?: 'default' | 'sm' | 'lg' | 'icon'
  defaultVariant?: 'default' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link'
}

interface UseActionUIResult {
  variant: ComputedRef<'default' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link'>
  size: ComputedRef<'default' | 'sm' | 'lg' | 'icon'>
  iconComponent: ComputedRef<any>
  iconClasses: ComputedRef<string>
  colorClasses: ComputedRef<string>
}

/**
 * Color mapping - mapeia cores genéricas para variants do shadcn
 */
const colorToVariantMap: Record<string, 'default' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link'> = {
  'green': 'default',
  'blue': 'default',
  'red': 'destructive',
  'yellow': 'outline',
  'gray': 'secondary',
  'default': 'default'
}

/**
 * Tailwind color classes para links
 */
const colorToTextClassMap: Record<string, string> = {
  'green': 'text-green-600 hover:text-green-700 dark:text-green-400',
  'blue': 'text-blue-600 hover:text-blue-700 dark:text-blue-400',
  'red': 'text-red-600 hover:text-red-700 dark:text-red-400',
  'yellow': 'text-yellow-600 hover:text-yellow-700 dark:text-yellow-400',
  'gray': 'text-gray-600 hover:text-gray-700 dark:text-gray-400',
  'default': 'text-primary hover:text-primary/80'
}

/**
 * Icon size mapping baseado no tamanho do botão
 */
const iconSizeMap: Record<'default' | 'sm' | 'lg' | 'icon', string> = {
  'sm': 'h-3 w-3',
  'default': 'h-3.5 w-3.5',
  'lg': 'h-4 w-4',
  'icon': 'h-4 w-4'
}

/**
 * Composable para gerenciar UI de ações
 */
export function useActionUI(options: UseActionUIOptions): UseActionUIResult {
  const { action, defaultSize = 'sm', defaultVariant } = options

  /**
   * Computa a variant do botão
   * Prioridade: explicit variant > color mapping > default
   */
  const variant = computed(() => {
    // 1. Se variant foi definido explicitamente, usa ele
    if (action.variant) return action.variant

    // 2. Se defaultVariant foi passado, usa ele
    if (defaultVariant) return defaultVariant

    // 3. Mapeia a cor para variant
    return colorToVariantMap[action.color || 'default'] || 'default'
  })

  /**
   * Computa o tamanho do botão
   */
  const size = computed(() => {
    return (action.size || defaultSize) as 'default' | 'sm' | 'lg' | 'icon'
  })

  /**
   * Computa as classes do ícone baseado no tamanho
   */
  const iconClasses = computed(() => {
    return iconSizeMap[size.value] || iconSizeMap.default
  })

  /**
   * Computa as classes de cor para links
   */
  const colorClasses = computed(() => {
    return colorToTextClassMap[action.color || 'default'] || colorToTextClassMap.default
  })

  /**
   * Carrega o componente do ícone dinamicamente
   */
  const iconComponent = computed(() => {
    if (!action.icon) return null

    const IconComponent = (LucideIcons as any)[action.icon]

    if (!IconComponent) {
      console.warn(`Icon "${action.icon}" not found in lucide-vue-next`)
      return null
    }

    return h(IconComponent)
  })

  return {
    variant,
    size,
    iconComponent,
    iconClasses,
    colorClasses
  }
}

/**
 * Helper para obter as classes do ícone com margens
 */
export function getIconClassesWithMargin(
  iconClasses: string,
  hasLabel: boolean = true,
  position: 'before' | 'after' = 'before'
): string {
  if (!hasLabel) return iconClasses

  const marginClass = position === 'before' ? 'mr-1.5' : 'ml-1.5'
  return `${iconClasses} ${marginClass}`
}
