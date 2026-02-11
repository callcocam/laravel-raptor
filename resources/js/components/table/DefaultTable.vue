<template>
  <div class="space-y-4">
    <!-- Filtros e Header Actions -->
    <div class="flex flex-col space-y-4">
      <TableFilters
        v-if="table.filters.value.length"
        :filters="table.filters.value"
        :searchable="table.searchable.value"
        @apply="table.filter"
        @clear="table.reset"
        class="flex-1"
      />
    </div>

    <div class="rounded-lg border bg-card">
      <div v-if="table.records.value.length" class="divide-y">
        <div
          v-for="record in table.records.value"
          :key="record.id"
          class="p-4 hover:bg-accent/50 transition-colors"
        >
          <div :class="tableGridClasses" class="grid">
            <div
              v-for="column in visibleColumns"
              :key="column.name"
              :class="getColumnClasses(column)"
              :style="getColumnStyles(column)"
            >
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
import { computed } from "vue";
import { useInertiaTable } from "~/composables/useInertiaTable";
import { useGridLayout } from "~/composables/useGridLayout";
import TableFilters from "../filters/TableFilters.vue";
import TablePagination from "./TablePagination.vue";
import ActionRenderer from "../actions/ActionRenderer.vue";
import TableColumnRenderer from "./TableColumnRenderer.vue";

const props = withDefaults(
  defineProps<{
    tableKey?: string;
  }>(),
  {
    tableKey: "table",
  }
);

const table = useInertiaTable(props.tableKey);

const columns = computed(() => table.columns.value);

const firstColumnWithGrid = computed(() =>
  columns.value.find((c) => c.gridColumns != null || c.gap != null)
);

const { getFormClasses, getColumnClasses, getColumnStyles } = useGridLayout({
  gridColumns: firstColumnWithGrid.value?.gridColumns ?? "3",
  gap: firstColumnWithGrid.value?.gap ?? "3",
});

const tableGridClasses = computed(() => {
  const gridColumns = firstColumnWithGrid.value?.gridColumns ?? "12";
  const gap = firstColumnWithGrid.value?.gap ?? "1";
  return getFormClasses(gridColumns, gap);
});

const visibleColumns = computed(() => {
  const cols = columns.value.filter((c) => c.visible !== false);
  return cols.sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
});

/**
 * Converte actions de objeto para array e filtra visíveis
 */
const getActions = (record: any) => {
  if (!record.actions) return [];

  if (Array.isArray(record.actions)) {
    return record.actions.filter((a: any) => a.visible !== false);
  }

  return Object.values(record.actions).filter((a: any) => a.visible !== false);
};
</script>
