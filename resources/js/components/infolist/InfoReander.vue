<template>
  <component :is="component" :column="column" />
</template>
<script lang="ts" setup>
import { computed } from "vue";
import ComponentRegistry from "../../utils/ComponentRegistry";

const props = defineProps({
  column: {
    type: Object,
    required: true,
  },
});

/**
 * Obtém o componente a ser renderizado do ComponentRegistry
 *
 * Usa o campo 'component' da coluna (ex: 'info-column-text')
 * Fallback para 'info-column-text' se não encontrado
 */
const component = computed(() => {
  const componentName = props.column.component || 'info-column-text';

  // Tenta obter do registry
  const registeredComponent = ComponentRegistry.get(componentName);

  if (registeredComponent) {
    return registeredComponent;
  }

  // Fallback para componente padrão
  const fallback = ComponentRegistry.get('info-column-text');

  if (!fallback) {
    console.warn(`Component '${componentName}' not found in registry and no fallback available`);
  }

  return fallback;
});
</script>
