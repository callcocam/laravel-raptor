<template>
  <div class="space-y-4">
    <!-- Filtros e Header Actions -->
    <div class="flex flex-wrap items-start justify-between gap-3">
      <TableFilters v-if="table.filters.value.length || table.searchable.value" :filters="table.filters.value"
        :searchable="table.searchable.value" class="flex-1 min-w-0" />
      <HeaderActions v-if="table.headerActions.value.length" :actions="table.headerActions.value" class="shrink-0" />
    </div>

    <div v-if="table.records.value.length" class="space-y-6">
      <Card v-for="record in table.records.value" :key="record.id"
        class="flex flex-col overflow-hidden transition-shadow hover:shadow-lg lg:flex-row">
        <!-- Thumbnail: colunas com rowSpan (ex: imagem) -->
        <div v-if="thumbnailColumns.length"
          class="relative h-32 w-full shrink-0 border-r bg-muted/30 lg:h-auto lg:w-48">
          <div class="flex h-full w-full flex-col items-center justify-center gap-2 p-2">
            <div v-for="column in thumbnailColumns" :key="column.name"
              class="flex h-full w-full items-center justify-center">
              <TableColumnRenderer :record="record" :column="column" />
            </div>
          </div>
        </div>

        <!-- Conteúdo: colunas sem rowSpan -->
        <div class="flex min-w-0 flex-1 flex-col">
          <CardContent :class="['grid flex-1 grid-cols-1 gap-2', contentGridClasses]">
            <div v-for="column in contentColumns" :key="column.name" :class="getColumnClasses(column)"
              :style="getColumnStyles(column)" class="flex flex-col mb-4">
              <span class="mb-1 text-[10px] font-bold uppercase text-muted-foreground">
                {{ column.label }}
              </span>
              <div class="text-sm font-semibold text-card-foreground">
                <TableColumnRenderer :record="record" :column="column" />
              </div>
              <!-- Colunas filhas: info secundária abaixo do valor principal -->
              <div v-if="column.columns?.length" class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                <div v-for="childCol in column.columns" :key="childCol.name"
                  class="inline-flex items-center text-xs text-muted-foreground">
                  <span v-if="childCol.label" class="mr-1 font-medium text-muted-foreground/60">
                    {{ childCol.label }}:
                  </span>
                  <TableColumnRenderer :record="record" :column="childCol" />
                </div>
              </div>
            </div>
          </CardContent>
          <CardFooter v-if="getActions(record).length" class="border-t pt-4">
            <div class="flex flex-wrap items-center gap-3">
              <ActionRenderer v-for="action in getActions(record)" :key="action.name" :action="action"
                :record="record" />
            </div>
          </CardFooter>
        </div>
      </Card>
    </div>

    <div v-else class="rounded-lg border bg-card p-12 text-center text-muted-foreground">
      Nenhum registro encontrado
    </div>

    <TablePagination v-if="table.meta.value?.total > 0" :meta="table.meta.value" />
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useInertiaTable } from "~/composables/useInertiaTable";
import { useGridLayout } from "~/composables/useGridLayout";
import { Card, CardContent, CardFooter } from '~/components/ui/card';
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

const columns = computed(() => table.columns.value);

const firstColumnWithGrid = computed(() =>
  columns.value.find((c) => c.gridColumns != null || c.gap != null)
);

const { getFormClasses, getColumnClasses, getColumnStyles } = useGridLayout({
  gridColumns: firstColumnWithGrid.value?.gridColumns ?? "3",
  gap: firstColumnWithGrid.value?.gap ?? "3",
});

const visibleColumns = computed(() => {
  const cols = columns.value.filter((c) => c.visible !== false);
  return cols.sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
});

/** Colunas com rowSpan → área thumbnail (ex: imagem à esquerda) */
const thumbnailColumns = computed(() =>
  visibleColumns.value.filter((c) => c.rowSpan != null && c.rowSpan !== "")
);

/** Colunas sem rowSpan → grid de conteúdo */
const contentColumns = computed(() =>
  visibleColumns.value.filter((c) => !c.rowSpan || c.rowSpan === "")
);

/** Classes do grid de conteúdo (dinâmico ou md:grid-cols-3 padrão) */
const contentGridClasses = computed(() => {
  const gridCols = firstColumnWithGrid.value?.gridColumns ?? "12";
  const gap = firstColumnWithGrid.value?.gap ?? "4";
  return getFormClasses(gridCols, gap);
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
