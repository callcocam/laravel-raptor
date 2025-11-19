/**
 * FilterRegistry - Sistema de registro de componentes de filtro
 * 
 * Permite registrar e recuperar componentes de filtro dinamicamente
 * Similar ao ComponentRegistry, mas específico para filtros
 */

import type { Component } from 'vue'

class FilterRegistryClass {
  private components: Map<string, Component> = new Map()
  private initialized = false

  /**
   * Registra um componente de filtro
   */
  register(name: string, component: Component): void {
    if (this.components.has(name)) {
      console.warn(`Filter component '${name}' is being overwritten`)
    }
    this.components.set(name, component)
  }

  /**
   * Registra múltiplos componentes de uma vez
   */
  registerBulk(components: Record<string, Component>): void {
    Object.entries(components).forEach(([name, component]) => {
      this.register(name, component)
    })
  }

  /**
   * Recupera um componente de filtro
   */
  get(name: string): Component | undefined {
    return this.components.get(name)
  }

  /**
   * Verifica se um componente está registrado
   */
  has(name: string): boolean {
    return this.components.has(name)
  }

  /**
   * Lista todos os componentes registrados
   */
  list(): string[] {
    return Array.from(this.components.keys())
  }

  /**
   * Remove um componente
   */
  unregister(name: string): void {
    this.components.delete(name)
  }

  /**
   * Limpa todos os componentes
   */
  clear(): void {
    this.components.clear()
    this.initialized = false
  }

  /**
   * Marca como inicializado
   */
  markAsInitialized(): void {
    this.initialized = true
  }

  /**
   * Verifica se está inicializado
   */
  isInitialized(): boolean {
    return this.initialized
  }
}

// Exporta uma instância singleton
const FilterRegistry = new FilterRegistryClass()

export default FilterRegistry
