<!--
 * HeaderActions - Renderiza ações do cabeçalho da tabela
 *
 * Exibe botões de ação como: Criar, Importar, Exportar, etc.
 * Usa ActionRenderer para renderizar componentes dinâmicos
 -->
<template>
  <div v-if="visibleActions.length > 0" class="flex items-center gap-2">
    <ActionRenderer
      v-for="action in visibleActions"
      :key="action.name"
      :action="action"
      @click="handleActionClick(action)"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import ActionRenderer from "../actions/ActionRenderer.vue";
import type { TableAction } from "../../types/table";

interface Props {
  actions: TableAction[];
}
const props = defineProps<Props>();
console.log('Não estou chegando aqui', props.actions);

const emit = defineEmits<{
  (e: "action-click", action: TableAction): void;
}>();

// Filtra apenas ações visíveis
const visibleActions = computed(() => {
  return props.actions.filter((action) => action.visible !== false);
});

// Handler de clique em ação
const handleActionClick = (action: TableAction) => {
  // Se tem confirmação, o pai deve mostrar dialog
  if (action.confirm) {
    emit("action-click", action);
    return;
  }

  // Se o target é modal, o pai deve abrir modal
  if (action.target === "modal") {
    emit("action-click", action);
    return;
  }

  // Se é um link simples (GET), navega
  if (action.method === "GET" && action.url && action.url !== "#") {
    emit("action-click", action);
    return;
  }

  // Outros casos, emite evento para o pai decidir
  emit("action-click", action);
};
</script>
