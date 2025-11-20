<template>
  <div class="space-y-4">
    <!-- Filtros e Header Actions -->
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
      <TableFilters
        v-if="table.filters.value.length"
        :filters="table.filters.value"
        @apply="table.filter"
        @clear="table.reset"
        class="flex-1"
      />
      
      <!-- Header Actions -->
      <HeaderActions
        v-if="table.headerActions.value.length"
        :actions="table.headerActions.value"
      />
    </div>

    <div class="rounded-lg border bg-card">
      <div v-if="table.records.value.length" class="divide-y">
        <div
          v-for="record in table.records.value"
          :key="record.id"
          class="p-4 hover:bg-accent/50 transition-colors"
        >
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="column in table.columns.value" :key="column.name">
              <span class="text-xs font-medium text-muted-foreground">
                {{ column.label }}
              </span>
              <div class="text-sm mt-0.5">
                <TableColumnRenderer :record="record" :column="column" />
              </div>
            </div>
          </div>

          <div v-if="getActions(record).length" class="flex gap-2 mt-3 pt-3 border-t">
            <ActionRenderer
              v-for="action in getActions(record)"
              :key="action.name"
              :action="action"
              :record="record"
            />
          </div>
        </div>
      </div>

      <div v-else class="p-12 text-center text-muted-foreground">
        Nenhum registro encontrado
      </div>
    </div>

    <TablePagination
      v-if="table.meta.value.total > 0"
      :meta="table.meta.value"
      @page-change="table.page"
      @per-page-change="table.perPage"
    />
  </div>
</template>

<script setup lang="ts">
import { useInertiaTable } from "~/composables/useInertiaTable";
import TableFilters from "../filters/TableFilters.vue";
import TablePagination from "./TablePagination.vue";
import ActionRenderer from "../actions/ActionRenderer.vue";
import TableColumnRenderer from "./TableColumnRenderer.vue";
import HeaderActions from "./HeaderActions.vue";

const props = withDefaults(
  defineProps<{
    tableKey?: string;
  }>(),
  {
    tableKey: "table",
  }
);

const table = useInertiaTable(props.tableKey);

/**
 * Converte actions de objeto para array e filtra visíveis
 */
const getActions = (record: any) => {
  if (!record.actions) return [];

  // Se já é array, filtra diretamente
  if (Array.isArray(record.actions)) {
    return record.actions.filter((a: any) => a.visible !== false);
  }

  // Se é objeto, converte para array
  return Object.values(record.actions).filter((a: any) => a.visible !== false);
};
</script>
