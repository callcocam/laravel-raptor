import { computed } from 'vue'

/**
 * Composable para gerenciar classes de layout
 */
export function useLayout(maxWidth: string = '7xl') {
  /**
   * Mapeamento de larguras máximas para classes Tailwind
   */
  const maxWidthClasses = {
    full: 'max-w-full',
    '7xl': 'max-w-7xl',
    '6xl': 'max-w-6xl',
    '5xl': 'max-w-5xl',
    '4xl': 'max-w-4xl',
    '3xl': 'max-w-3xl',
    '2xl': 'max-w-2xl',
    xl: 'max-w-xl',
    lg: 'max-w-lg',
    md: 'max-w-md',
    sm: 'max-w-sm',
    xs: 'max-w-xs'
  }

  /**
   * Retorna a classe de largura máxima baseada no valor configurado
   */
  const containerClass = computed(() => {
    return maxWidthClasses[maxWidth as keyof typeof maxWidthClasses] || maxWidthClasses['7xl']
  })

  /**
   * Retorna as classes completas do container incluindo centralização
   */
  const containerClasses = computed(() => {
    return `${containerClass.value} mx-auto w-full`
  })

  return {
    containerClass,
    containerClasses
  }
}
