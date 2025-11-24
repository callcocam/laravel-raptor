<!--
 * InfoListRenderer - Renderiza infolist columns em modais
 * 
 * Exibe informações detalhadas em formato de lista de informações
 * Suporta CardColumn, TextColumn, etc.
 -->
<template>
  <div class="space-y-4">
    <div v-if="getColumns && getColumns.length > 0">
      <div v-for="(column, index) in getColumns" :key="column.name || index">
        <InfoRenderer :column="getValue(column)" />
      </div>
    </div>

    <div v-else class="p-12 text-center text-muted-foreground rounded-lg border bg-card">
      Nenhuma informação disponível
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import InfoRenderer from "./InfoRenderer.vue";

interface InfoColumn {
  name: string;
  label?: string;
  component?: string;
  value?: any;
  columns?: any[];
  [key: string]: any;
}

interface Props {
  columns?: InfoColumn[];
  value?: Record<string, any>;
  name?: string;
}
const props = withDefaults(defineProps<Props>(), {
  columns: () => [],
  value: () => ({}),
  name: () => "",
});

const getColumns = computed(() => {
  return (
    props.columns.filter(
      (column) => column.name !== "actionType" && column.name !== "actionName"
    ) || []
  );
});

const getValue = (column: InfoColumn) => {
  // Função helper para acessar valores aninhados usando notação de ponto
  const getNestedValue = (obj: any, path: string) => {
    if (!obj || !path) return undefined;
    
    const keys = path.split('.');
    let value = obj;
    
    for (const key of keys) {
      if (value && typeof value === 'object' && key in value) {
        value = value[key];
      } else {
        return undefined;
      }
    }
    
    return value;
  };
  
  // Buscar valor usando notação de ponto
  const columnValue = getNestedValue(props.value, column.name);
  
  return {
    ...column,
    value: columnValue,
    default: columnValue,
  };
};
</script>
