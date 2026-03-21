<!--
 * RowTable — layout de tabela em linhas horizontais compactas.
 *
 * Cada registro é renderizado como uma linha com:
 *   - Thumbnail à esquerda (colunas com rowSpan)
 *   - Coluna principal (primeira sem rowSpan) — nome/título em destaque
 *   - Colunas de dados — label pequena em uppercase + valor
 *   - Ações ícone alinhadas à direita
 *
 * Suporte a tabs de filtro no topo (via table.data.tabs ou prop tabs).
-->
<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useInertiaTable } from '~/composables/useInertiaTable'
import { useGridLayout } from '~/composables/useGridLayout'
import TableFilters from '../filters/TableFilters.vue'
import TablePagination from './TablePagination.vue'
import TableColumnRenderer from './TableColumnRenderer.vue'
import ActionRenderer from '../actions/ActionRenderer.vue'
import HeaderActions from './HeaderActions.vue'

interface Tab {
    label: string
    value: string | null
    count?: number
}

const props = withDefaults(defineProps<{
    tableKey?: string
    /** Tabs de filtro rápido exibidas acima das linhas */
    tabs?: Tab[]
    /** Nome do query param usado pelas tabs */
    tabParam?: string
}>(), {
    tableKey: 'table',
    tabs: () => [],
    tabParam: 'tab',
})

const table = useInertiaTable(props.tableKey)

// ── Tabs ──────────────────────────────────────────────────────────────────────

/** Tabs vindas da prop ou do backend (table.data.value.tabs) */
const resolvedTabs = computed<Tab[]>(() =>
    props.tabs.length > 0
        ? props.tabs
        : (table.data.value.tabs as Tab[] | undefined) ?? []
)

const currentUrl = computed(() => new URL(window.location.href))
const activeTab = computed(() =>
    currentUrl.value.searchParams.get(props.tabParam) ?? null
)

const navigateTab = (value: string | null) => {
    const params = Object.fromEntries(currentUrl.value.searchParams)
    delete params.page
    if (value === null) {
        delete params[props.tabParam]
    } else {
        params[props.tabParam] = value
    }
    router.get(window.location.pathname, params, {
        preserveState: true,
        preserveScroll: true,
    })
}

// ── Colunas ───────────────────────────────────────────────────────────────────

const columns = computed(() =>
    table.columns.value
        .filter((c: any) => c.visible !== false)
        .sort((a: any, b: any) => (a.order ?? 0) - (b.order ?? 0))
)

/** Colunas com rowSpan → thumbnail lateral */
const thumbnailCols = computed(() =>
    columns.value.filter((c: any) => c.rowSpan != null && c.rowSpan !== '')
)

const nonThumbnailCols = computed(() =>
    columns.value.filter((c: any) => !c.rowSpan || c.rowSpan === '')
)

/**
 * Coluna principal: a marcada com primary:true, ou como fallback
 * a primeira coluna não-compacta (evita pegar toggles/status).
 */
const primaryCol = computed(() => {
    const cols = nonThumbnailCols.value
    return (
        cols.find((c: any) => c.primary === true) ??
        cols.find((c: any) => !isCompactComponent(c.component ?? '')) ??
        cols[0]
    )
})

const COMPACT_PREFIXES = [
    'table-column-boolean',
    'table-column-status',
    'table-column-toggle',
    'table-column-badge',
]

const isCompactComponent = (component: string): boolean =>
    COMPACT_PREFIXES.some((prefix) => component === prefix || component.startsWith(prefix + '-'))

/** Colunas compactas (toggle/status e variantes -editable) — renderizadas junto ao thumbnail */
const compactCols = computed(() =>
    nonThumbnailCols.value.filter(
        (c: any) => c !== primaryCol.value && isCompactComponent(c.component ?? '')
    )
)

/** Demais colunas sem rowSpan que não são a primary nem compactas → células de dados */
const dataCols = computed(() =>
    nonThumbnailCols.value.filter(
        (c: any) => c !== primaryCol.value && !isCompactComponent(c.component ?? '')
    )
)

// ── Grid do conteúdo da linha (usa o mesmo useGridLayout do DefaultTable) ──────

const { getColumnClasses } = useGridLayout({ gridColumns: '12', gap: '4' })

/**
 * No RowTable o grid é sempre horizontal (sem breakpoint md:).
 * Substitui 'md:col-span-X' → 'col-span-X' e 'md:grid-cols-X' → 'grid-cols-X'.
 * Colunas com 'full' span são normalizadas para o espaço restante.
 */
const toRowClasses = (classes: string): string =>
    classes.replace(/md:/g, '')

/** Span numérico de uma coluna (normaliza 'full' → ocupa o restante até 12) */
const resolveSpan = (col: any, reserved: number): number => {
    if (col.columnSpan === 'full') return Math.max(1, 12 - reserved)
    return Math.min(12, Math.max(1, Number(col.columnSpan) || 3))
}

/**
 * Constrói as classes col-span-X para cada coluna do conteúdo da linha,
 * garantindo que a soma total não ultrapasse 12 cols.
 */
const rowColClasses = computed(() => {
    const primarySpan = resolveSpan(primaryCol.value ?? {}, 0)
    let used = primarySpan

    const dataSpans = dataCols.value.map((col: any) => {
        const span = resolveSpan(col, used + 1 /* reserva 1 pra actions */)
        used += span
        return span
    })

    const actionsSpan = Math.max(1, 12 - used)

    return {
        primary: toRowClasses(getColumnClasses({ ...primaryCol.value, columnSpan: String(primarySpan) })),
        data: dataSpans.map((span, i) =>
            toRowClasses(getColumnClasses({ ...dataCols.value[i], columnSpan: String(span) }))
        ),
        actions: `col-span-${actionsSpan}`,
    }
})

// ── Ações ────────────────────────────────────────────────────────────────────

const getRowActions = (record: any): any[] => {
    if (!record.actions) return []
    const list = Array.isArray(record.actions)
        ? record.actions
        : Object.values(record.actions)
    return list.filter((a: any) => a.visible !== false)
}

</script>

<template>
    <div class="flex flex-col gap-3">

        <!-- Filtros + ações de header -->
        <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <TableFilters
                    v-if="table.filters.value.length || table.searchable.value"
                    :filters="table.filters.value"
                    :searchable="table.searchable.value"
                    class="flex-1 min-w-0"
                />
                <HeaderActions
                    v-if="table.headerActions.value.length"
                    :actions="table.headerActions.value"
                    class="shrink-0"
                />
            </div>
        </div>

        <!-- Tabs de filtro rápido -->
        <div v-if="resolvedTabs.length > 0" class="flex flex-wrap gap-1 border-b pb-2">
            <button
                v-for="tab in resolvedTabs"
                :key="String(tab.value)"
                type="button"
                :class="[
                    'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold uppercase tracking-wider transition-colors',
                    (activeTab === tab.value || (activeTab === null && tab.value === null))
                        ? 'bg-primary text-primary-foreground shadow-sm'
                        : 'bg-muted text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                ]"
                @click="navigateTab(tab.value)"
            >
                {{ tab.label }}
                <span
                    v-if="tab.count !== undefined"
                    :class="[
                        'rounded px-1 py-0.5 text-[10px] font-bold',
                        (activeTab === tab.value || (activeTab === null && tab.value === null))
                            ? 'bg-primary-foreground/20 text-primary-foreground'
                            : 'bg-background text-foreground',
                    ]"
                >{{ tab.count }}</span>
            </button>
        </div>

        <!-- Linhas -->
        <div v-if="table.records.value.length" class="flex flex-col divide-y divide-border rounded-lg border bg-card">
            <div
                v-for="record in table.records.value"
                :key="record.id"
                class="group flex min-h-[64px] items-stretch gap-0 transition-colors hover:bg-muted/40"
            >
                <!-- Thumbnail + colunas compactas (status/toggle) -->
                <div
                    v-if="thumbnailCols.length || compactCols.length"
                    class="flex shrink-0 items-center gap-2 border-r bg-muted/30 px-2"
                    :class="thumbnailCols.length ? 'w-auto' : 'w-12'"
                >
                    <div
                        v-for="col in thumbnailCols"
                        :key="col.name"
                        class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-md"
                    >
                        <TableColumnRenderer :record="record" :column="col" />
                    </div>
                    <div
                        v-for="col in compactCols"
                        :key="col.name"
                        class="flex items-center justify-center"
                    >
                        <TableColumnRenderer :record="record" :column="col" />
                    </div>
                </div>

                <!-- Conteúdo da linha — grid-cols-12 igual ao DefaultTable -->
                <div class="grid grid-cols-12 min-w-0 flex-1 items-center gap-x-3 overflow-hidden px-4 py-2">

                    <!-- Coluna principal -->
                    <div
                        v-if="primaryCol"
                        class="flex flex-col justify-center gap-0.5 overflow-hidden"
                        :class="rowColClasses.primary"
                    >
                        <span v-if="primaryCol.label" class="mb-0.5 text-[9px] font-bold uppercase tracking-widest text-muted-foreground">
                            {{ primaryCol.label }}
                        </span>
                        <div class="truncate text-sm font-semibold leading-tight text-foreground">
                            <TableColumnRenderer :record="record" :column="primaryCol" />
                        </div>
                        <div v-if="primaryCol.columns?.length" class="flex flex-wrap items-center gap-x-3 gap-y-0.5">
                            <div
                                v-for="childCol in primaryCol.columns"
                                :key="childCol.name"
                                class="inline-flex items-center text-xs text-muted-foreground"
                            >
                                <span v-if="childCol.label" class="mr-1 font-medium text-muted-foreground/60">
                                    {{ childCol.label }}:
                                </span>
                                <TableColumnRenderer :record="record" :column="childCol" />
                            </div>
                        </div>
                    </div>

                    <!-- Data cols -->
                    <div
                        v-for="(col, idx) in dataCols"
                        :key="col.name"
                        class="hidden flex-col overflow-hidden sm:flex"
                        :class="rowColClasses.data[idx]"
                    >
                        <span class="mb-0.5 text-[9px] font-bold uppercase tracking-widest text-muted-foreground">
                            {{ col.label }}
                        </span>
                        <div class="truncate text-sm font-medium text-foreground">
                            <TableColumnRenderer :record="record" :column="col" />
                        </div>
                        <div v-if="col.columns?.length" class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <div
                                v-for="childCol in col.columns"
                                :key="childCol.name"
                                class="inline-flex items-center gap-0.5 text-[10px] text-muted-foreground"
                            >
                                <span v-if="childCol.label" class="font-medium text-muted-foreground/60">
                                    {{ childCol.label }}:
                                </span>
                                <TableColumnRenderer :record="record" :column="childCol" />
                            </div>
                        </div>
                    </div>

                    <!-- Ações -->
                    <div
                        class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                        :class="rowColClasses.actions"
                    >
                        <ActionRenderer
                            v-for="action in getRowActions(record)"
                            :key="action.name"
                            :action="action"
                            :record="record"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado vazio -->
        <div
            v-else
            class="flex flex-col items-center justify-center gap-2 rounded-lg border bg-muted/20 py-16 text-center text-muted-foreground"
        >
            <span class="text-sm">Nenhum registro encontrado</span>
        </div>

        <!-- Rodapé: paginação (inclui resumo internamente) -->
        <TablePagination
            v-if="table.meta.value?.last_page > 1 || table.meta.value?.total > 0"
            :meta="table.meta.value"
        />

    </div>
</template>
