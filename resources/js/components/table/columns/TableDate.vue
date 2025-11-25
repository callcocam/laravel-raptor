<template>
  <span :title="column.tooltip">{{ value }}</span>
</template>

<script lang="ts" setup>
import { computed } from "vue";

const props = defineProps<{
  record: Record<string, any>;
  column: {
    name: string;
    format?: string;
    tooltip?: string;
    [key: string]: any;
  };
}>();

/**
 * Obtém o valor da propriedade, suportando acesso aninhado (ex: 'category.name')
 * Prioriza valores formatados (ex: 'email_formatted' sobre 'email')
 */
const value = computed(() => {
  // Primeiro tenta buscar o valor formatado (ex: email_formatted)
  const formattedKey = `${props.column.name}_formatted`;

  if (props.record && typeof props.record === "object" && formattedKey in props.record) {
    return props.record[formattedKey] ?? "";
  }

  // Se não houver versão formatada, busca o valor original
  const keys = props.column.name.split(".");
  let result = props.record;

  for (const key of keys) {
    if (result && typeof result === "object" && key in result) {
      result = result[key];
    } else {
      return "";
    }
  }

  return result ?? "";
});
</script>
