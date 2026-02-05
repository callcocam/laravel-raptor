/**
 * useTheme - Composable para gerenciamento de temas
 *
 * Gerencia a aplicação de temas (cores, fontes, variantes) na aplicação.
 * Os temas são aplicados através de classes CSS no elemento raiz.
 *
 * @example
 * const { theme, setTheme, availableThemes } = useTheme()
 * setTheme({ color: 'blue', font: 'inter', rounded: 'medium' })
 *
 * // Adicionar novo tema
 * addTheme({ name: 'custom', label: 'Personalizado', color: 'custom' })
 *
 * // Remover tema
 * removeTheme('custom')
 */

import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

export interface ThemeConfig {
  color?: string
  font?: string
  rounded?: string
  variant?: string
}

export interface Theme extends ThemeConfig {
  name: string
  label: string
}

export interface ThemeOption {
  value: string
  label: string
}

// Temas disponíveis (reativo para permitir alterações dinâmicas)
const availableThemes = ref<Theme[]>([
  { name: 'default', label: 'Padrão', color: 'default' },
  { name: 'blue', label: 'Azul', color: 'blue' },
  { name: 'green', label: 'Verde', color: 'green' },
  { name: 'amber', label: 'Âmbar', color: 'amber' },
  { name: 'rose', label: 'Rosa', color: 'rose' },
  { name: 'purple', label: 'Roxo', color: 'purple' },
  { name: 'orange', label: 'Laranja', color: 'orange' },
  { name: 'teal', label: 'Azul Turquesa', color: 'teal' },
  { name: 'red', label: 'Vermelho', color: 'red' },
  { name: 'yellow', label: 'Amarelo', color: 'yellow' },
  { name: 'violet', label: 'Violeta', color: 'violet' },
  { name: 'plannerate', label: 'Plannerate', color: 'plannerate' },
])

const availableFonts = ref<ThemeOption[]>([
  { value: 'default', label: 'Padrão (Geist)' },
  { value: 'inter', label: 'Inter' },
  { value: 'noto-sans', label: 'Noto Sans' },
  { value: 'nunito-sans', label: 'Nunito Sans' },
  { value: 'figtree', label: 'Figtree' },
])

const availableRounded = ref<ThemeOption[]>([
  { value: 'none', label: 'Nenhum' },
  { value: 'small', label: 'Pequeno' },
  { value: 'medium', label: 'Médio' },
  { value: 'large', label: 'Grande' },
  { value: 'full', label: 'Completo' },
])

const availableVariants = ref<ThemeOption[]>([
  { value: 'default', label: 'Padrão' },
  { value: 'mono', label: 'Monoespaçado' },
  { value: 'scaled', label: 'Escalado' },
])

// ==================== Funções para gerenciar TEMAS ====================

/**
 * Adiciona um novo tema à lista de temas disponíveis
 */
export function addTheme(theme: Theme): boolean {
  const exists = availableThemes.value.some(t => t.name === theme.name)
  if (exists) {
    console.warn(`Tema "${theme.name}" já existe.`)
    return false
  }
  availableThemes.value.push(theme)
  return true
}

/**
 * Remove um tema da lista de temas disponíveis
 */
export function removeTheme(name: string): boolean {
  if (name === 'default') {
    console.warn('Não é possível remover o tema padrão.')
    return false
  }
  const index = availableThemes.value.findIndex(t => t.name === name)
  if (index === -1) {
    console.warn(`Tema "${name}" não encontrado.`)
    return false
  }
  availableThemes.value.splice(index, 1)
  return true
}

/**
 * Atualiza um tema existente
 */
export function updateTheme(name: string, updates: Partial<Theme>): boolean {
  const theme = availableThemes.value.find(t => t.name === name)
  if (!theme) {
    console.warn(`Tema "${name}" não encontrado.`)
    return false
  }
  Object.assign(theme, updates)
  return true
}

// ==================== Funções para gerenciar FONTES ====================

/**
 * Adiciona uma nova fonte à lista de fontes disponíveis
 */
export function addFont(font: ThemeOption): boolean {
  const exists = availableFonts.value.some(f => f.value === font.value)
  if (exists) {
    console.warn(`Fonte "${font.value}" já existe.`)
    return false
  }
  availableFonts.value.push(font)
  return true
}

/**
 * Remove uma fonte da lista de fontes disponíveis
 */
export function removeFont(value: string): boolean {
  if (value === 'default') {
    console.warn('Não é possível remover a fonte padrão.')
    return false
  }
  const index = availableFonts.value.findIndex(f => f.value === value)
  if (index === -1) {
    console.warn(`Fonte "${value}" não encontrada.`)
    return false
  }
  availableFonts.value.splice(index, 1)
  return true
}

/**
 * Atualiza uma fonte existente
 */
export function updateFont(value: string, updates: Partial<ThemeOption>): boolean {
  const font = availableFonts.value.find(f => f.value === value)
  if (!font) {
    console.warn(`Fonte "${value}" não encontrada.`)
    return false
  }
  Object.assign(font, updates)
  return true
}

// ==================== Funções para gerenciar ARREDONDAMENTOS ====================

/**
 * Adiciona um novo arredondamento à lista de arredondamentos disponíveis
 */
export function addRounded(rounded: ThemeOption): boolean {
  const exists = availableRounded.value.some(r => r.value === rounded.value)
  if (exists) {
    console.warn(`Arredondamento "${rounded.value}" já existe.`)
    return false
  }
  availableRounded.value.push(rounded)
  return true
}

/**
 * Remove um arredondamento da lista de arredondamentos disponíveis
 */
export function removeRounded(value: string): boolean {
  if (value === 'medium') {
    console.warn('Não é possível remover o arredondamento padrão (medium).')
    return false
  }
  const index = availableRounded.value.findIndex(r => r.value === value)
  if (index === -1) {
    console.warn(`Arredondamento "${value}" não encontrado.`)
    return false
  }
  availableRounded.value.splice(index, 1)
  return true
}

/**
 * Atualiza um arredondamento existente
 */
export function updateRounded(value: string, updates: Partial<ThemeOption>): boolean {
  const rounded = availableRounded.value.find(r => r.value === value)
  if (!rounded) {
    console.warn(`Arredondamento "${value}" não encontrado.`)
    return false
  }
  Object.assign(rounded, updates)
  return true
}

// ==================== Funções para gerenciar VARIANTES ====================

/**
 * Adiciona uma nova variante à lista de variantes disponíveis
 */
export function addVariant(variant: ThemeOption): boolean {
  const exists = availableVariants.value.some(v => v.value === variant.value)
  if (exists) {
    console.warn(`Variante "${variant.value}" já existe.`)
    return false
  }
  availableVariants.value.push(variant)
  return true
}

/**
 * Remove uma variante da lista de variantes disponíveis
 */
export function removeVariant(value: string): boolean {
  if (value === 'default') {
    console.warn('Não é possível remover a variante padrão.')
    return false
  }
  const index = availableVariants.value.findIndex(v => v.value === value)
  if (index === -1) {
    console.warn(`Variante "${value}" não encontrada.`)
    return false
  }
  availableVariants.value.splice(index, 1)
  return true
}

/**
 * Atualiza uma variante existente
 */
export function updateVariant(value: string, updates: Partial<ThemeOption>): boolean {
  const variant = availableVariants.value.find(v => v.value === value)
  if (!variant) {
    console.warn(`Variante "${value}" não encontrada.`)
    return false
  }
  Object.assign(variant, updates)
  return true
}

// ==================== Funções para definir listas completas ====================

/**
 * Define a lista completa de temas disponíveis
 */
export function setAvailableThemes(themes: Theme[]): void {
  availableThemes.value = themes
}

/**
 * Define a lista completa de fontes disponíveis
 */
export function setAvailableFonts(fonts: ThemeOption[]): void {
  availableFonts.value = fonts
}

/**
 * Define a lista completa de arredondamentos disponíveis
 */
export function setAvailableRounded(rounded: ThemeOption[]): void {
  availableRounded.value = rounded
}

/**
 * Define a lista completa de variantes disponíveis
 */
export function setAvailableVariants(variants: ThemeOption[]): void {
  availableVariants.value = variants
}

// Estado global do tema
const currentTheme = ref<ThemeConfig>({
  color: 'default',
  font: 'default',
  rounded: 'medium',
  variant: 'default',
})

// Chave para persistência no localStorage
const STORAGE_KEY = 'app-theme'

/**
 * Carrega o tema salvo do localStorage
 */
function loadThemeFromStorage(): ThemeConfig {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved) {
      return JSON.parse(saved)
    }
  } catch (error) {
    console.error('Erro ao carregar tema do localStorage:', error)
  }
  return currentTheme.value
}

/**
 * Salva o tema no localStorage
 */
function saveThemeToStorage(theme: ThemeConfig) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(theme))
  } catch (error) {
    console.error('Erro ao salvar tema no localStorage:', error)
  }
}

/**
 * Salva o tema no servidor
 */
async function saveThemeToServer(theme: ThemeConfig) {
  try {

    router.put('/tenant/update-theme', { ...theme }, {
      preserveState: true,
      preserveScroll: true, 
    })
  } catch (error) {
    console.error('Error saving theme to server:', error)
  }
}

/**
 * Aplica as classes de tema no elemento raiz
 */
function applyThemeClasses(theme: ThemeConfig) {
  const html = document.documentElement
  const body = document.body

  // Adiciona a classe theme-container no body se não existir
  if (!body.classList.contains('theme-container')) {
    body.classList.add('theme-container')
  }

  // Remove todas as classes de tema existentes
  html.classList.forEach((className) => {
    if (className.startsWith('theme-')) {
      html.classList.remove(className)
    }
  })

  // Aplica as novas classes de tema
  if (theme.color && theme.color !== 'default') {
    html.classList.add(`theme-${theme.color}`)
  }

  if (theme.font && theme.font !== 'default') {
    html.classList.add(`theme-${theme.font}`)
  }

  if (theme.rounded && theme.rounded !== 'medium') {
    html.classList.add(`theme-rounded-${theme.rounded}`)
  }

  if (theme.variant && theme.variant !== 'default') {
    html.classList.add(`theme-${theme.variant}`)
  }
}

/**
 * Inicializa o sistema de temas
 * Deve ser chamado uma vez no app.ts
 *
 * @param serverTheme Tema carregado do servidor (tem prioridade sobre localStorage)
 */
export function initializeThemeSystem(serverTheme?: ThemeConfig | null) {
  if (typeof window !== 'undefined') {
    // Prefer server theme over localStorage
    const savedTheme = serverTheme || loadThemeFromStorage()
    currentTheme.value = savedTheme
    applyThemeClasses(savedTheme)
  }
}

/**
 * Composable para gerenciamento de temas
 */
export function useTheme() {
  // Garante que o tema está aplicado
  if (typeof window !== 'undefined' && !document.body.classList.contains('theme-container')) {
    applyThemeClasses(currentTheme.value)
  }

  // Observa mudanças no tema e aplica as classes
  watch(currentTheme, (newTheme) => {
    applyThemeClasses(newTheme)
    saveThemeToStorage(newTheme)
    saveThemeToServer(newTheme)
  }, { deep: true })

  /**
   * Define um novo tema
   */
  function setTheme(theme: Partial<ThemeConfig>) {
    currentTheme.value = {
      ...currentTheme.value,
      ...theme,
    }
  }

  /**
   * Define a cor do tema
   */
  function setColor(color: string) {
    setTheme({ color })
  }

  /**
   * Define a fonte do tema
   */
  function setFont(font: string) {
    setTheme({ font })
  }

  /**
   * Define o arredondamento do tema
   */
  function setRounded(rounded: string) {
    setTheme({ rounded })
  }

  /**
   * Define a variante do tema
   */
  function setVariant(variant: string) {
    setTheme({ variant })
  }

  /**
   * Reseta o tema para o padrão
   */
  function resetTheme() {
    currentTheme.value = {
      color: 'default',
      font: 'default',
      rounded: 'medium',
      variant: 'default',
    }
  }

  /**
   * Tema atual como computed
   */
  const theme = computed(() => currentTheme.value)

  return {
    // Estado atual
    theme,
    
    // Setters do tema atual
    setTheme,
    setColor,
    setFont,
    setRounded,
    setVariant,
    resetTheme,
    
    // Listas disponíveis (reativas)
    availableThemes: computed(() => availableThemes.value),
    availableFonts: computed(() => availableFonts.value),
    availableRounded: computed(() => availableRounded.value),
    availableVariants: computed(() => availableVariants.value),
    
    // Gerenciamento de Temas
    addTheme,
    removeTheme,
    updateTheme,
    setAvailableThemes,
    
    // Gerenciamento de Fontes
    addFont,
    removeFont,
    updateFont,
    setAvailableFonts,
    
    // Gerenciamento de Arredondamentos
    addRounded,
    removeRounded,
    updateRounded,
    setAvailableRounded,
    
    // Gerenciamento de Variantes
    addVariant,
    removeVariant,
    updateVariant,
    setAvailableVariants,
  }
}
