<!--
 * FormActions - Renderiza ações de formulário
 *
 * Usa o ActionRenderer para renderizar ações dinamicamente
 * Suporta ações de submit e cancel com estado de loading
 -->
<script setup lang="ts">
import { computed } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import ActionRenderer from '~/components/actions/ActionRenderer.vue'
import type { TableAction } from '~/types/table'

interface Props {
  actions?: TableAction[]
  processing?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  actions: () => [],
  processing: false,
})

/**
 * Prepara as ações para renderização
 * Adiciona o componente correto baseado no actionType e o estado de processing
 */
const preparedActions = computed(() => {
  return props.actions.map(action => ({
    ...action,
    component: action.component || getComponentForActionType(action.actionType || ''),
    processing: props.processing,
  }))
})

function getComponentForActionType(actionType: string): string {
  const typeMap: Record<string, string> = {
    'submit': 'action-form-button',
    'cancel': 'action-form-button',
  }
  return typeMap[actionType] || 'action-button'
}
</script>

<template>
  <div class="flex items-center justify-end gap-2">
    <Loader2
      v-if="processing"
      class="size-4 shrink-0 animate-spin text-muted-foreground"
      aria-hidden="true"
    />
    <ActionRenderer
      v-for="action in preparedActions"
      :key="action.name"
      :action="action"
    />
  </div>
</template>
