/**
 * Tipos para o sistema de Table
 * Baseado em TableBuilder.php e table.json
 */

/**
 * Action da tabela (linha, header, bulk)
 */
export interface TableAction {
  name: string
  label: string
  type: string | null
  actionType?: 'link' | 'api' | 'callback' | 'modal' | 'table' | 'submit' | 'cancel'
  icon: string
  tooltip: string
  options: any[]
  component: string
  method: string
  target: string
  url: string
  to: string | object
  color: string | null
  confirm: TableActionConfirm | null
  position: string | null
  action?: string
  visible: boolean
  authorized?: boolean
  callback?: string
  columns?: any[]
  inertia?: {
    preserveScroll?: boolean
    preserveState?: boolean
    only?: string[]
  }
  modalSize?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
  processingLabel?: string
  successMessage?: string
  errorMessage?: string
}

/**
 * Confirmação de ação
 */
export interface TableActionConfirm {
  title: string
  message: string
  confirmText?: string
  cancelText?: string
  confirmColor?: string
  text?: string
  confirmButtonText?: string
  cancelButtonText?: string
  successMessage?: string
  requiresTypedConfirmation?: boolean
  typedConfirmationWord?: string
  closeModalOnSuccess?: boolean
}

/**
 * Coluna da tabela
 */
export interface TableColumn {
  name: string
  label: string
  type: string
  icon: string
  tooltip: string
  options: any[]
  component: string
  visible: boolean
  gridColumns?: string
  columnSpan?: string
  order?: number
  gap?: string
  responsive?: {
    grid?: {
      sm?: string
      md?: string
      lg?: string
      xl?: string
    }
    span?: {
      sm?: string
      md?: string
      lg?: string
      xl?: string
    }
  }
}

/**
 * Filtro da tabela
 */
export interface TableFilter {
  id: string
  name: string
  label: string
  icon: string
  component: string
  context: any
  value?: any
}

/**
 * Item/registro da tabela
 */
export interface TableRecord {
  id: string
  [key: string]: any
  actions?: Record<string, TableAction>
}
 
/**
 * Metadados de paginação
 */
export interface TableMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
  path: string
  has_more_pages: boolean,
  links: Array<{
    url: string | null
    label: string
    page: number | null
    active: boolean
  }>
}

/**
 * Breadcrumb item
 */
export interface TableBreadcrumb {
  title: string
  href: string
  current: boolean
}

/**
 * Estrutura completa da resposta da tabela
 */
export interface TableResponse {
  data: TableRecord[]
  meta: TableMeta
  columns: TableColumn[]
  bulkActions: TableAction[]
  filters: TableFilter[]
  headerActions: TableAction[]
  isSearcheable: boolean
  title: string
  description: string | null
  breadcrumbs: TableBreadcrumb[]
  hasBulkActions: boolean
  queryParams: Record<string, any>
}

/**
 * Configuração de ordenação
 */
export interface TableSort {
  column: string
  direction: 'asc' | 'desc'
}

/**
 * Parâmetros de query para a tabela
 */
export interface TableQueryParams {
  page?: number
  per_page?: number
  search?: string
  sort?: string
  direction?: 'asc' | 'desc'
  filters?: Record<string, any>
}

/**
 * Estado da tabela
 */
export interface TableState {
  data: TableRecord[]
  meta: TableMeta
  columns: TableColumn[]
  bulkActions: TableAction[]
  filters: TableFilter[]
  headerActions: TableAction[]
  isSearcheable: boolean
  title: string
  description: string | null
  breadcrumbs: TableBreadcrumb[]
  hasBulkActions: boolean
  queryParams: TableQueryParams
  loading: boolean
  error: string | null
  selectedRows: string[]
}

/**
 * Props do componente Table
 */
export interface TableProps {
  /**
   * Nome do resource (ex: 'users')
   */
  resource: string

  /**
   * Endpoint customizado (opcional)
   */
  endpoint?: string

  /**
   * Parâmetros iniciais de query
   */
  initialParams?: TableQueryParams

  /**
   * Colunas visíveis (override)
   */
  visibleColumns?: string[]

  /**
   * Mostrar paginação
   */
  showPagination?: boolean

  /**
   * Mostrar busca
   */
  showSearch?: boolean

  /**
   * Mostrar filtros
   */
  showFilters?: boolean

  /**
   * Mostrar ações em massa
   */
  showBulkActions?: boolean

  /**
   * Mostrar ações de header
   */
  showHeaderActions?: boolean

  /**
   * Altura da tabela (scroll)
   */
  height?: string

  /**
   * Permite seleção múltipla
   */
  selectable?: boolean
}

/**
 * Eventos emitidos pelo componente Table
 */
export interface TableEmits {
  (e: 'row-click', record: TableRecord): void
  (e: 'row-select', ids: string[]): void
  (e: 'action-click', action: TableAction, record?: TableRecord): void
  (e: 'bulk-action-click', action: TableAction, records: TableRecord[]): void
  (e: 'filter-change', filters: Record<string, any>): void
  (e: 'sort-change', sort: TableSort): void
  (e: 'page-change', page: number): void
  (e: 'search', query: string): void
}
