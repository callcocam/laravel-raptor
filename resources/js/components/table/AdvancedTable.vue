<template>
  <div class="space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="inline-flex items-center rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-green-700 shadow-sm dark:bg-slate-700 dark:text-green-400"
          >
            Lista
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
            disabled
          >
            Kanban
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
            disabled
          >
            Mapa
          </button>
        </div>

        <div class="flex items-center gap-2" v-if="table.headerActions.value?.length">
          <ActionRenderer
            v-for="action in visibleHeaderActions"
            :key="action.name"
            :action="action"
          />
        </div>
      </div>

      <div class="mt-4">
        <TableFilters
          v-if="table.filters.value.length || table.searchable.value"
          :filters="table.filters.value"
          :searchable="table.searchable.value"
          @apply="table.filter"
          @clear="table.reset"
        />
      </div>
    </div>

    <div class="space-y-4" v-if="table.records.value.length">
      <div
        v-for="record in table.records.value"
        :key="record.id"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
      >
        <div class="flex flex-col lg:flex-row lg:items-stretch">
          <div class="relative h-36 w-full shrink-0 bg-slate-100 dark:bg-slate-800 lg:h-auto lg:w-52">
            <img
              v-if="getRecordImage(record)"
              :src="getRecordImage(record)"
              :alt="getRecordTitle(record)"
              class="h-full w-full object-cover"
            />
            <div
              v-else
              class="flex h-full w-full items-center justify-center text-xs font-semibold uppercase tracking-wide text-slate-400"
            >
              Sem imagem
            </div>
          </div>

          <div class="flex-1 p-4">
            <div class="flex flex-col gap-3">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase" :class="getStatusClasses(record)">
                  {{ getRecordStatus(record) }}
                </span>
                <span class="text-xs text-slate-400" v-if="getRecordTag(record)">
                  {{ getRecordTag(record) }}
                </span>
              </div>

              <h3 class="text-base font-bold text-slate-900 dark:text-white">
                {{ getRecordTitle(record) }}
              </h3>

              <p v-if="getRecordPath(record)" class="text-sm text-slate-500 dark:text-slate-400">
                {{ getRecordPath(record) }}
              </p>
            </div>

            <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 dark:border-slate-800 md:grid-cols-3">
              <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold">
                  <span class="text-slate-500">Progresso</span>
                  <span class="text-lime-600 dark:text-lime-400">{{ getProgress(record) }}%</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                  <div
                    class="h-full rounded-full bg-lime-400"
                    :style="{ width: `${getProgress(record)}%` }"
                  />
                </div>
              </div>

              <div class="flex flex-col">
                <span class="text-[10px] font-bold uppercase text-slate-400">Data de inicio</span>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ getStartDate(record) }}</span>
              </div>

              <div class="flex flex-col">
                <span class="text-[10px] font-bold uppercase text-slate-400">Prazo de termino</span>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ getEndDate(record) }}</span>
              </div>
            </div>

            <div class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-800" v-if="getActions(record).length">
              <div class="flex flex-wrap items-center gap-2">
                <ActionRenderer
                  v-for="action in getActions(record)"
                  :key="`${record.id}-${action.name}`"
                  :action="action"
                  :record="record"
                />
              </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="column in visibleColumns"
                :key="column.name"
                :class="getColumnClasses(column)"
                :style="getColumnStyles(column)"
                class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/60"
              >
                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                  {{ column.label }}
                </span>
                <div class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                  <TableColumnRenderer :record="record" :column="column" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
      Nenhum registro encontrado
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
import { computed } from 'vue'
import { useInertiaTable } from '../../composables/useInertiaTable'
import { useGridLayout } from '../../composables/useGridLayout'
import type { TableColumn } from '../../types/table'
import TableFilters from '../filters/TableFilters.vue'
import TablePagination from './TablePagination.vue'
import ActionRenderer from '../actions/ActionRenderer.vue'
import TableColumnRenderer from './TableColumnRenderer.vue'

const props = withDefaults(
  defineProps<{
    tableKey?: string
  }>(),
  {
    tableKey: 'table',
  },
)

const table = useInertiaTable(props.tableKey)

const columns = computed<TableColumn[]>(() => table.columns.value as TableColumn[])

const firstColumnWithGrid = computed(() =>
  columns.value.find((column: TableColumn) => column.gridColumns != null || column.gap != null),
)

const { getColumnClasses, getColumnStyles } = useGridLayout({
  gridColumns: firstColumnWithGrid.value?.gridColumns ?? '3',
  gap: firstColumnWithGrid.value?.gap ?? '3',
})

const visibleColumns = computed(() => {
  const cols = columns.value.filter((column: TableColumn) => column.visible !== false)
  return cols.sort((a: TableColumn, b: TableColumn) => (a.order ?? 0) - (b.order ?? 0))
})

const visibleHeaderActions = computed(() =>
  (table.headerActions.value || []).filter((action: any) => action.visible !== false),
)

const getActions = (record: any) => {
  if (!record?.actions) {
    return []
  }

  if (Array.isArray(record.actions)) {
    return record.actions.filter((action: any) => action.visible !== false)
  }

  return Object.values(record.actions).filter((action: any) => action.visible !== false)
}

const getRecordTitle = (record: any): string => {
  return record?.title || record?.name || record?.label || record?.id || 'Registro'
}

const getRecordStatus = (record: any): string => {
  return record?.status || record?.state || 'Ativo'
}

const getRecordTag = (record: any): string => {
  return record?.category || record?.group || ''
}

const getRecordPath = (record: any): string => {
  return record?.path || record?.location || record?.description || ''
}

const normalizePercent = (value: any): number => {
  const numericValue = Number(value)
  if (!Number.isFinite(numericValue)) {
    return 0
  }

  return Math.min(100, Math.max(0, Math.round(numericValue)))
}

const getProgress = (record: any): number => {
  return normalizePercent(record?.progress ?? record?.percentage ?? record?.completion)
}

const getStartDate = (record: any): string => {
  return record?.start_date || record?.created_at || '-'
}

const getEndDate = (record: any): string => {
  return record?.end_date || record?.updated_at || '-'
}

const getRecordImage = (record: any): string => {
  return record?.image || record?.image_url || record?.thumbnail || ''
}

const getStatusClasses = (record: any): string => {
  const status = String(getRecordStatus(record)).toLowerCase()

  if (status.includes('progress') || status.includes('andamento')) {
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
  }

  if (status.includes('cancel')) {
    return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
  }

  if (status.includes('done') || status.includes('conclu')) {
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
  }

  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}
</script>
