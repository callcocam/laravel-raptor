/**
 * useActionUI - Composable para lógica comum de UI em actions
 *
 * Centraliza a lógica repetida em componentes de ações:
 * - Mapeamento de cor para variant
 * - Estilo plannerate (fundo escuro + ícone em caixa verde) vs Button padrão
 * - Configurações de tamanho e variante
 */

import { computed, h, type ComputedRef } from 'vue'
import * as LucideIcons from 'lucide-vue-next'
import type { TableAction } from '~/types/table'

export type ActionVariant = 'default' | 'create' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link' | 'success' | 'warning'

/**
 * Estilo base - alinhado ao Button padrão (btn-gradient + ActionIconBox)
 * Usa as mesmas classes do sistema para consistência visual
 */
export const ACTION_STYLE = {
  buttonClasses:
    'flex items-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-3 py-[5px] text-xs font-medium text-white shadow-xs transition-all hover:border-slate-500 hover:bg-slate-700 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none btn-gradient',
  buttonClassesDisabled: 'disabled:opacity-50 disabled:pointer-events-none',
  iconWrapperClasses:
    '',
  iconClasses: 'size-5',
  labelClasses: 'text-sm font-semibold text-white',
} as const

/** Variantes que usam o componente Button (não o estilo plannerate) */
const BUTTON_VARIANTS: ActionVariant[] = ['default', 'create', 'outline', 'ghost', 'destructive', 'secondary', 'link', 'success', 'warning']

/** Verifica se a variant deve usar o componente Button em vez do estilo plannerate */
export function isButtonVariant(variant: ActionVariant): boolean {
  return BUTTON_VARIANTS.includes(variant)
}

interface UseActionUIOptions {
  action: TableAction
  defaultSize?: 'default' | 'sm' | 'lg' | 'icon'
  defaultVariant?: ActionVariant
}

interface UseActionUIResult {
  variant: ComputedRef<ActionVariant>
  size: ComputedRef<'default' | 'sm' | 'lg' | 'icon'>
  iconComponent: ComputedRef<any>
  iconClasses: ComputedRef<string>
  colorClasses: ComputedRef<string>
  /** True quando deve usar o estilo plannerate (fundo escuro + ícone verde). False para outline/ghost/etc. */
  isActionStyle: ComputedRef<boolean>
  /** Constante com classes do estilo plannerate */
  actionStyle: typeof ACTION_STYLE
  /** Variant para ActionIconBox (default | outline | destructive) baseado na variant do botão */
  iconBoxVariant: ComputedRef<'default' | 'outline' | 'destructive'>
}

/**
 * Color mapping - mapeia cores genéricas para variants do shadcn
 */
const colorToVariantMap: Record<string, ActionVariant> = {
  'green': 'create',
  'blue': 'default',
  'red': 'destructive',
  'yellow': 'warning',
  'gray': 'secondary',
  'default': 'default'
}

/**
 * Classes de cor para links - usa variáveis do tema
 */
const colorToTextClassMap: Record<string, string> = {
  'green': 'text-primary hover:text-primary/80',
  'blue': 'text-primary hover:text-primary/80',
  'red': 'text-destructive hover:text-destructive/90',
  'yellow': 'text-muted-foreground hover:text-foreground',
  'gray': 'text-muted-foreground hover:text-foreground',
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

  const isActionStyle = computed(() => !isButtonVariant(variant.value))

  const iconBoxVariant = computed((): 'default' | 'outline' | 'destructive' => {
    const v = variant.value
    if (v === 'destructive') return 'destructive'
    if (v === 'create' || v === 'success') return 'default'
    if (v === 'default' || v === 'outline' || v === 'secondary' || v === 'ghost' || v === 'warning' || v === 'link') return 'outline'
    return 'default'
  })

  return {
    variant,
    size,
    iconComponent,
    iconClasses,
    colorClasses,
    isActionStyle,
    actionStyle: ACTION_STYLE,
    iconBoxVariant,
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
