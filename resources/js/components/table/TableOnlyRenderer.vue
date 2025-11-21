<!--
 * TableOnlyRenderer - Renderiza apenas colunas de tabela sem paginação/filtros
 * 
 * Usado em modais para exibir listas simples de dados relacionados
 * Não possui paginação, filtros ou ações - apenas visualização
 -->
<template>
  <div class="space-y-4">
    <div v-if="data && data.length > 0" class="rounded-lg border bg-card overflow-hidden">
      <div class="divide-y">
        <div
          v-for="(record, index) in data"
          :key="record.id || index"
          class="p-4 hover:bg-accent/50 transition-colors"
        >
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="column in columns" :key="column.name">
              <span class="text-xs font-medium text-muted-foreground">
                {{ column.label }}
              </span>
              <div class="text-sm mt-0.5">
                <TableColumnRenderer :record="record" :column="column" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="p-12 text-center text-muted-foreground rounded-lg border bg-card">
      Nenhum registro encontrado
    </div>
  </div>
</template>

<script setup lang="ts">
import TableColumnRenderer from "./TableColumnRenderer.vue";

interface TableColumn {
  name: string;
  label: string;
  component?: string;
  searchable?: boolean;
  sortable?: boolean;
  visible?: boolean;
  [key: string]: any;
}

interface Props {
  columns?: TableColumn[];
  data?: any[];
}

withDefaults(defineProps<Props>(), {
  columns: () => [],
  data: () => [],
});
</script>
