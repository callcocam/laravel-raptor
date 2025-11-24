<!--
 * BreadcrumbRenderer - Renderiza breadcrumbs dinamicamente
 * 
 * Usa o BreadcrumbRegistry para obter o componente correto
 * Similar ao InfoRenderer.vue
 * Suporta slot para passar header actions
 -->
<template>
  <component :is="component" :items="items" :config="config">
    <slot />
  </component>
</template>

<script lang="ts" setup>
import { computed, type Component } from 'vue'
import BreadcrumbRegistry from '../../utils/BreadcrumbRegistry'

export interface BreadcrumbItemData {
  label: string
  href?: string
  icon?: string | Component
  active?: boolean
}

export interface BreadcrumbConfigData {
  component?: string
  separator?: string
  maxItems?: number
  showHome?: boolean
  homeLabel?: string
  homeHref?: string
  homeIcon?: Component
  [key: string]: any
}

const props = withDefaults(defineProps<{
  items: BreadcrumbItemData[]
  config?: BreadcrumbConfigData
}>(), {
  config: () => ({})
})

/**
 * Obtém o componente a ser renderizado do BreadcrumbRegistry
 *
 * Usa o campo 'component' da config (ex: 'breadcrumb-default')
 * Fallback para 'breadcrumb-default' se não encontrado
 */
const component = computed(() => {
  const componentName = props.config?.component || 'breadcrumb-default'

  // Tenta obter do registry
  const registeredComponent = BreadcrumbRegistry.get(componentName)

  if (registeredComponent) {
    return registeredComponent
  }

  // Fallback para componente padrão
  const fallback = BreadcrumbRegistry.get('breadcrumb-default')

  if (!fallback) {
    console.warn(`Component '${componentName}' not found in registry and no fallback available`)
  }

  return fallback
})
</script>

