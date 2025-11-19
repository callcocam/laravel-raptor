/**
 * useTable - Composable para gerenciar estado e operações da tabela
 * 
 * Integra com TableBuilder.php do backend
 */

import { ref, computed, onMounted } from 'vue'
import type {
  TableResponse,
  TableState,
  TableQueryParams,
  TableRecord,
  TableAction,
  TableSort
} from '../types/table'

export interface UseTableOptions {
  resource: string
  endpoint?: string
  initialParams?: TableQueryParams
  autoLoad?: boolean
}

export function useTable(options: UseTableOptions) {
  const {
    resource,
    endpoint,
    initialParams = {},
    autoLoad = true
  } = options

  // Estado reativo
  const state = ref<TableState>({
    data: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0,
      path: '',
      has_more_pages: false
    },
    columns: [],
    bulkActions: [],
    filters: [],
    headerActions: [],
    isSearcheable: false,
    title: '',
    description: null,
    breadcrumbs: [],
    hasBulkActions: false,
    queryParams: {
      page: 1,
      per_page: 15,
      ...initialParams
    },
    loading: false,
    error: null,
    selectedRows: []
  })

  /**
   * Gera URL do endpoint
   */
  const getEndpoint = computed(() => {
    if (endpoint) return endpoint
    return `/api/${resource}`
  })

  /**
   * Carrega dados da tabela
   */
  const load = async (params?: TableQueryParams) => {
    state.value.loading = true
    state.value.error = null

    try {
      const queryParams = { ...state.value.queryParams, ...params }
      
      // Construir query string
      const queryString = new URLSearchParams(
        Object.entries(queryParams).reduce((acc, [key, value]) => {
          if (value !== null && value !== undefined && value !== '') {
            acc[key] = String(value)
          }
          return acc
        }, {} as Record<string, string>)
      ).toString()

      const url = `${getEndpoint.value}${queryString ? `?${queryString}` : ''}`
      
      console.log('Loading table data from:', url)
      
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include'
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data: TableResponse = await response.json()
      
      // Atualizar estado
      state.value.data = data.data
      state.value.meta = data.meta
      state.value.columns = data.columns
      state.value.bulkActions = data.bulkActions
      state.value.filters = data.filters
      state.value.headerActions = data.headerActions
      state.value.isSearcheable = data.isSearcheable
      state.value.title = data.title
      state.value.description = data.description
      state.value.breadcrumbs = data.breadcrumbs
      state.value.hasBulkActions = data.hasBulkActions
      state.value.queryParams = { ...queryParams, ...data.queryParams } 
    } catch (error) {
      console.error('Error loading table:', error)
      state.value.error = error instanceof Error ? error.message : 'Erro ao carregar dados'
    } finally {
      state.value.loading = false
    }
  }

  /**
   * Recarrega os dados mantendo os parâmetros atuais
   */
  const reload = () => {
    return load()
  }

  /**
   * Busca por texto
   */
  const search = (query: string) => {
    return load({ ...state.value.queryParams, search: query, page: 1 })
  }

  /**
   * Muda a página
   */
  const changePage = (page: number) => {
    return load({ ...state.value.queryParams, page })
  }

  /**
   * Muda a ordenação
   */
  const changeSort = (sort: TableSort) => {
    return load({
      ...state.value.queryParams,
      sort: sort.column,
      direction: sort.direction,
      page: 1
    })
  }

  /**
   * Aplica filtros
   */
  const applyFilters = (filters: Record<string, any>) => {
    return load({
      ...state.value.queryParams,
      filters,
      page: 1
    })
  }

  /**
   * Limpa filtros
   */
  const clearFilters = () => {
    return load({
      ...state.value.queryParams,
      filters: {},
      page: 1
    })
  }

  /**
   * Seleciona/deseleciona uma linha
   */
  const toggleRow = (id: string) => {
    const index = state.value.selectedRows.indexOf(id)
    if (index > -1) {
      state.value.selectedRows.splice(index, 1)
    } else {
      state.value.selectedRows.push(id)
    }
  }

  /**
   * Seleciona todas as linhas
   */
  const selectAll = () => {
    state.value.selectedRows = state.value.data.map(row => row.id)
  }

  /**
   * Deseleciona todas as linhas
   */
  const deselectAll = () => {
    state.value.selectedRows = []
  }

  /**
   * Verifica se uma linha está selecionada
   */
  const isRowSelected = (id: string) => {
    return state.value.selectedRows.includes(id)
  }

  /**
   * Verifica se todas as linhas estão selecionadas
   */
  const isAllSelected = computed(() => {
    return state.value.data.length > 0 && 
           state.value.selectedRows.length === state.value.data.length
  })

  /**
   * Verifica se alguma linha está selecionada
   */
  const hasSelected = computed(() => {
    return state.value.selectedRows.length > 0
  })

  /**
   * Obtém os registros selecionados
   */
  const selectedRecords = computed(() => {
    return state.value.data.filter(row => state.value.selectedRows.includes(row.id))
  })

  /**
   * Executa uma ação
   */
  const executeAction = async (action: TableAction, record?: TableRecord) => {
    console.log('Executing action:', action.name, record)
    
    // Se tem confirmação, deveria mostrar modal antes
    if (action.confirm) {
      // O componente deve lidar com isso
      return
    }

    try {
      const response = await fetch(action.url, {
        method: action.method,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include',
        body: record ? JSON.stringify(record) : undefined
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      // Recarrega dados após ação
      await reload()
      
      return await response.json()
    } catch (error) {
      console.error('Error executing action:', error)
      throw error
    }
  }

  /**
   * Executa ação em massa
   */
  const executeBulkAction = async (action: TableAction) => {
    if (state.value.selectedRows.length === 0) {
      throw new Error('Nenhuma linha selecionada')
    }

    console.log('Executing bulk action:', action.name, state.value.selectedRows)

    try {
      const response = await fetch(action.url, {
        method: action.method,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include',
        body: JSON.stringify({ ids: state.value.selectedRows })
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      // Limpa seleção e recarrega
      deselectAll()
      await reload()
      
      return await response.json()
    } catch (error) {
      console.error('Error executing bulk action:', error)
      throw error
    }
  }

  // Auto-load na montagem
  if (autoLoad) {
    onMounted(() => {
      load()
    })
  }

  return {
    // Estado
    state,
    
    // Computeds
    isAllSelected,
    hasSelected,
    selectedRecords,
    
    // Métodos de carregamento
    load,
    reload,
    
    // Métodos de interação
    search,
    changePage,
    changeSort,
    applyFilters,
    clearFilters,
    
    // Métodos de seleção
    toggleRow,
    selectAll,
    deselectAll,
    isRowSelected,
    
    // Métodos de ação
    executeAction,
    executeBulkAction
  }
}

