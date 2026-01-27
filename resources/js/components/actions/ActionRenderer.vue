<!--
 * ActionRenderer - Renderiza ações dinamicamente
 *
 * Usa o ActionRegistry para obter o componente correto
 * Similar ao InfoRenderer.vue
 -->
<template>
  <component :is="component" :action="action" :record="record" :column="column" @click="handleClick" />
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import ActionRegistry from '~/utils/ActionRegistry'
import type { TableAction } from '~/types/table'

const props = defineProps<{
  action: TableAction
  record?: Record<string, any>
  column?: Record<string, any>
}>() 
const emit = defineEmits<{
  (e: 'click', event: Event): void
}>()
/**
 * Obtém o componente a ser renderizado do ActionRegistry
 *
 * Lógica de seleção (prioridade decrescente):
 * 1. Se 'component' está especificado → usa ele
 * 2. Se 'actionType' está definido → usa mapeamento de tipo
 * 3. Auto-detecção baseada em propriedades (legado)
 * 4. Fallback para 'action-button'
 *
 * Mapeamento de actionType:
 * - 'link' → 'action-link' (navegação GET)
 * - 'callback' → 'action-callback' (executa função JS)
 * - 'modal' → 'action-modal-form' (modal com formulário)
 * - 'api' → 'action-confirm' (API call com confirmação)
 * - 'table' → 'action-modal-table' (futuro)
 */
const component = computed(() => {
  let componentName = props.action.component

  // 1. Prioridade: componente especificado explicitamente
  if (componentName) {
    const registeredComponent = ActionRegistry.get(componentName)
    if (registeredComponent) {
      return registeredComponent
    }
  }

  // 2. Usa actionType se disponível (nova arquitetura)
  if (props.action.actionType) {
    const typeMap: Record<string, string> = {
      'link': 'action-link',
      'callback': 'action-callback',
      'modal': 'action-modal-form',
      'api': 'action-confirm',
      'table': 'action-modal-table', // futuro
    }

    componentName = typeMap[props.action.actionType]

    if (componentName) {
      const registeredComponent = ActionRegistry.get(componentName)
      if (registeredComponent) {
        return registeredComponent
      }
    }
  }

  // 3. Auto-detecção baseada nas propriedades (backward compatibility)
  if (!componentName) {
    if (props.action.confirm && props.action.to) {
      componentName = 'action-link-confirm'
    } else if (props.action.confirm) {
      componentName = 'action-confirm'
    } else if (props.action.to) {
      componentName = 'action-link'
    } else if (props.action.target === 'modal') {
      componentName = 'action-modal'
    } else {
      componentName = 'action-button'
    }

    const registeredComponent = ActionRegistry.get(componentName)
    if (registeredComponent) {
      return registeredComponent
    }
  }

  // 4. Fallback para componente padrão
  const fallback = ActionRegistry.get('action-button')

  if (!fallback) {
    console.warn(`Component '${componentName}' not found in registry and no fallback available`)
  }

  return fallback
})

const handleClick = (event: Event) => {
  // Re-emite o evento de clique para o pai 
  emit('click', event)
}
</script>
