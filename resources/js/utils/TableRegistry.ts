/**
 * TableRegistry - Gerencia registro de componentes Table
 *
 * Permite registrar componentes padrão do pacote e componentes personalizados
 * da aplicação.
 *
 * @example
 * // Registrar componente personalizado
 * TableRegistry.register('table-custom', CustomComponent)
 *
 * // Sobrescrever componente padrão
 * TableRegistry.register('table-default', MyTableComponent)
 *
 * // Obter componente
 * const component = TableRegistry.get('table-default')
 */

import type { Component } from 'vue'

type ComponentMap = Record<string, Component>

class TableRegistryClass {
  private components: ComponentMap = {}
  private initialized = false

  /**
   * Registra um componente
   *
   * @param name Nome do componente (ex: 'table-default')
   * @param component Componente Vue
   */
  register(name: string, component: Component): void {
    this.components[name] = component
  }

  /**
   * Registra múltiplos componentes de uma vez
   *
   * @param components Objeto com mapa de componentes
   */
  registerBulk(components: ComponentMap): void {
    Object.entries(components).forEach(([name, component]) => {
      this.register(name, component)
    })
  }

  /**
   * Obtém um componente registrado
   *
   * @param name Nome do componente
   * @returns Componente Vue ou undefined
   */
  get(name: string): Component | undefined {
    return this.components[name]
  }

  /**
   * Verifica se um componente está registrado
   *
   * @param name Nome do componente
   * @returns true se existe, false caso contrário
   */
  has(name: string): boolean {
    return name in this.components
  }

  /**
   * Obtém todos os componentes registrados
   *
   * @returns Objeto com todos os componentes
   */
  getAll(): ComponentMap {
    return { ...this.components }
  }

  /**
   * Lista os nomes de todos os componentes registrados
   *
   * @returns Array com nomes dos componentes
   */
  list(): string[] {
    return Object.keys(this.components)
  }

  /**
   * Remove um componente do registry
   *
   * @param name Nome do componente
   */
  unregister(name: string): void {
    delete this.components[name]
  }

  /**
   * Limpa todos os componentes registrados
   */
  clear(): void {
    this.components = {}
    this.initialized = false
  }

  /**
   * Marca o registry como inicializado
   */
  markAsInitialized(): void {
    this.initialized = true
  }

  /**
   * Verifica se o registry foi inicializado
   */
  isInitialized(): boolean {
    return this.initialized
  }

  /**
   * Obtém estatísticas do registry
   */
  getStats(): {
    total: number
    initialized: boolean
    components: string[]
  } {
    return {
      total: Object.keys(this.components).length,
      initialized: this.initialized,
      components: this.list(),
    }
  }
}

// Exporta instância singleton
export const TableRegistry = new TableRegistryClass()

// Exporta também a classe para testes
export { TableRegistryClass }

// Export default para compatibilidade
export default TableRegistry

