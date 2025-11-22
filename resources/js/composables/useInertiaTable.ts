import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { TableMeta } from '~/types/table'

export function useInertiaTable(key = 'table') {
  const page = usePage()
  type TableData = {
    data?: any[]
    meta: TableMeta
    columns?: any[]
    filters?: any[]
    actions?: any[]
    headerActions?: any[]
    [key: string]: any
  }
  const data = computed<TableData>(() => page.props[key] as TableData || {}) 

  const navigate = (params: Record<string, any>) => {
    router.get(window.location.pathname, params, {
      preserveState: true,
      preserveScroll: true,
      only: [key]
    })
  }

  const query = computed(() => {
    const url = new URL(window.location.href)
    return Object.fromEntries(url.searchParams)
  })

  return {
    data,
    records: computed(() => data.value.data || []),
    meta: computed(() => data.value.meta || {}),
    columns: computed(() => data.value.columns || []),
    filters: computed(() => data.value.filters || []),
    actions: computed(() => data.value.actions || []),
    headerActions: computed(() => data.value.headerActions || []),

    page: (num: number) => navigate({ ...query.value, page: num }),
    perPage: (num: number) => navigate({ ...query.value, per_page: num, page: 1 }),
    search: (term: string) => navigate({ ...query.value, search: term || undefined, page: 1 }),
    filter: (filters: Record<string, any>) => navigate({ ...query.value, ...filters, page: 1 }),
    sort: (col: string, dir: 'asc' | 'desc') => navigate({ ...query.value, sort: col, direction: dir }),
    reset: () => navigate({}),
  }
}
