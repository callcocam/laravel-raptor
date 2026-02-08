import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useEcho, useConnectionStatus } from '@laravel/echo-vue'
import { toast } from 'vue-sonner'

// ============================================================================
// Types & Interfaces
// ============================================================================

export interface GlobalNotification {
    id: string
    type: 'info' | 'success' | 'warning' | 'error'
    title: string
    message?: string
    data?: Record<string, any>
    read_at?: string | null
    created_at: string
    notification_id?: string
    notification_type?: string
    /** Nome do evento que originou a notificação */
    event_name?: string
}

export interface EventPayload {
    [key: string]: any
}

/**
 * Handler customizado para processar eventos específicos
 * Retorna uma GlobalNotification ou null para ignorar o evento
 */
export type EventHandler = (
    eventName: string,
    payload: EventPayload
) => GlobalNotification | null

/**
 * Configuração de toast customizado para tipos específicos
 */
export interface ToastConfig {
    duration?: number
    action?: {
        label: string
        onClick: (notification: GlobalNotification) => void
    }
}

export type ToastHandler = (notification: GlobalNotification) => ToastConfig | void

// ============================================================================
// Event Handler Registry (Sistema Plugável)
// ============================================================================

const eventHandlers = new Map<string | RegExp, EventHandler>()
const toastHandlers = new Map<string, ToastHandler>()

/**
 * Registra um handler para um evento específico ou padrão regex
 * 
 * @example
 * ```ts
 * // Handler para evento específico
 * registerEventHandler('import.completed', (event, payload) => ({
 *     id: `import-${Date.now()}`,
 *     type: payload.failed > 0 ? 'warning' : 'success',
 *     title: payload.message || 'Importação concluída',
 *     message: `${payload.successful} de ${payload.total} registros`,
 *     data: payload,
 *     read_at: null,
 *     created_at: new Date().toISOString(),
 * }))
 * 
 * // Handler com regex para múltiplos eventos
 * registerEventHandler(/^order\.(created|updated|deleted)$/, (event, payload) => ({
 *     id: `order-${payload.order?.id}-${Date.now()}`,
 *     type: 'info',
 *     title: `Pedido ${event.split('.')[1]}`,
 *     message: `Pedido #${payload.order?.id}`,
 *     data: payload,
 *     read_at: null,
 *     created_at: new Date().toISOString(),
 * }))
 * ```
 */
export function registerEventHandler(
    eventPattern: string | RegExp,
    handler: EventHandler
): () => void {
    eventHandlers.set(eventPattern, handler)
    return () => eventHandlers.delete(eventPattern)
}

/**
 * Registra configuração de toast customizada para um tipo de notificação
 * 
 * @example
 * ```ts
 * registerToastHandler('export', (notification) => ({
 *     duration: 10000,
 *     action: {
 *         label: 'Download',
 *         onClick: () => window.location.href = notification.data?.downloadUrl
 *     }
 * }))
 * ```
 */
export function registerToastHandler(
    notificationType: string,
    handler: ToastHandler
): () => void {
    toastHandlers.set(notificationType, handler)
    return () => toastHandlers.delete(notificationType)
}

/**
 * Remove todos os handlers registrados
 */
export function clearAllHandlers(): void {
    eventHandlers.clear()
    toastHandlers.clear()
}

// ============================================================================
// Built-in Event Handlers (Handlers padrão)
// ============================================================================

function registerBuiltInHandlers() {
    // Handler para eventos de importação
    registerEventHandler(/^\.?import\.completed$/, (event, payload) => ({
        id: `import-${Date.now()}-${Math.random()}`,
        type: payload.failed > 0 ? 'warning' : 'success',
        title: payload.message || 'Importação concluída',
        message: payload.fileName ? `Arquivo: ${payload.fileName}` : undefined,
        data: {
            type: 'import',
            model: payload.model,
            total: payload.total,
            successful: payload.successful,
            failed: payload.failed,
            failed_report_path: payload.failed_report_path,
            failed_report_download: payload.failed_report_download,
        },
        read_at: null,
        created_at: payload.timestamp || new Date().toISOString(),
        event_name: event,
    }))

    // Handler para eventos de exportação
    registerEventHandler(/^\.?export\.completed$/, (event, payload) => ({
        id: `export-${Date.now()}-${Math.random()}`,
        type: 'success',
        title: payload.message || 'Exportação concluída',
        message: 'Clique para fazer o download',
        data: {
            type: 'export',
            model: payload.model,
            total: payload.total,
            downloadUrl: payload.downloadUrl,
            download: payload.downloadUrl,
            fileName: payload.fileName,
            action: 'download',
        },
        read_at: null,
        created_at: payload.timestamp || new Date().toISOString(),
        event_name: event,
    }))

    // Toast customizado para exportação
    registerToastHandler('export', (notification) => ({
        duration: 10000,
        action: {
            label: 'Download',
            onClick: () => {
                if (notification.data?.downloadUrl) {
                    window.location.href = notification.data.downloadUrl
                }
            },
        },
    }))

    // Toast para importação: botão "Baixar erros" quando houver relatório de falhas
    registerToastHandler('import', (notification) =>
        notification.data?.failed_report_download
            ? {
                  duration: 10000,
                  action: {
                      label: 'Baixar erros',
                      onClick: () => {
                          window.location.href = notification.data.failed_report_download
                      },
                  },
              }
            : undefined
    )

    // Handler para erros de banco de dados
    registerEventHandler(/^\.?database\.connection\.failed$/, (event, payload) => ({
        id: `db-error-${Date.now()}-${Math.random()}`,
        type: 'error',
        title: 'Erro de Conexão com Banco de Dados',
        message: payload.message || `Não foi possível conectar ao banco de dados '${payload.database}'.`,
        data: {
            database: payload.database,
            is_database_not_found: payload.is_database_not_found,
            resolution_steps: payload.resolution_steps || [],
            timestamp: payload.timestamp,
        },
        read_at: null,
        created_at: payload.timestamp || new Date().toISOString(),
        event_name: event,
    }))
}

// Registra handlers built-in na inicialização
registerBuiltInHandlers()

// ============================================================================
// Event Normalization (Normalização inteligente de eventos)
// ============================================================================

/**
 * Normaliza o nome do evento removendo prefixos
 */
function normalizeEventName(eventName: string): string {
    return eventName.replace(/^\./, '').replace(/^\\/, '')
}

/**
 * Encontra o handler apropriado para um evento
 */
function findHandler(eventName: string): EventHandler | null {
    const normalized = normalizeEventName(eventName)

    // Busca handler exato primeiro
    if (eventHandlers.has(eventName)) {
        return eventHandlers.get(eventName)!
    }
    if (eventHandlers.has(normalized)) {
        return eventHandlers.get(normalized)!
    }

    // Busca por regex
    for (const [pattern, handler] of eventHandlers) {
        if (pattern instanceof RegExp && pattern.test(normalized)) {
            return handler
        }
    }

    return null
}

/**
 * Normaliza qualquer payload em uma GlobalNotification
 * Usado quando não há handler específico registrado
 */
function normalizeToNotification(
    eventName: string,
    payload: EventPayload
): GlobalNotification {
    // Tenta extrair dados de diferentes estruturas possíveis
    const data = payload.notification || payload.data || payload
    
    return {
        id: data.id || payload.id || `notification-${Date.now()}-${Math.random()}`,
        type: data.type || payload.type || 'info',
        title: data.title || payload.title || data.message || payload.message || 'Nova notificação',
        message: data.message || payload.message || data.description || payload.description || data.body || payload.body,
        data: data.data || payload.data || data.metadata || payload.metadata || {},
        read_at: null,
        created_at: data.created_at || payload.created_at || data.timestamp || payload.timestamp || new Date().toISOString(),
        notification_id: data.id || payload.id,
        notification_type: data.notification_type || payload.notification_type || data.type || payload.type,
        event_name: eventName,
    }
}

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Cria uma notificação manualmente
 */
export function createNotification(
    type: 'info' | 'success' | 'warning' | 'error',
    title: string,
    message?: string,
    data?: Record<string, any>
): GlobalNotification {
    return {
        id: `notification-${Date.now()}-${Math.random()}`,
        type,
        title,
        message,
        data: data || {},
        read_at: null,
        created_at: new Date().toISOString(),
    }
}

// ============================================================================
// Main Composable
// ============================================================================

export function useGlobalNotifications() {
    const page = usePage()
    const notifications = ref<GlobalNotification[]>([])
    const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length)
    
    // Usa o composable nativo do @laravel/echo-vue para status da conexão
    const connectionStatus = useConnectionStatus()
    const isConnected = computed(() => connectionStatus.value === 'connected')

    // ========================================================================
    // Core Methods
    // ========================================================================

    const addNotification = (notification: GlobalNotification) => {
        // Evita duplicatas
        if (notifications.value.find(n => n.id === notification.id)) {
            return
        }

        notifications.value.unshift(notification)

        // Mantém apenas as últimas 100
        if (notifications.value.length > 100) {
            notifications.value = notifications.value.slice(0, 100)
        }

        showToast(notification)
    }

    const showToast = (notification: GlobalNotification) => {
        // Busca handler de toast customizado
        const dataType = notification.data?.type as string
        const customHandler = dataType ? toastHandlers.get(dataType) : null
        const customConfig = customHandler?.(notification)

        const toastOptions: any = {
            description: notification.message,
            duration: customConfig?.duration ?? (notification.type === 'error' ? 8000 : 5000),
        }

        if (customConfig?.action) {
            toastOptions.action = {
                label: customConfig.action.label,
                onClick: () => customConfig.action!.onClick(notification),
            }
        }

        switch (notification.type) {
            case 'success':
                toast.success(notification.title, toastOptions)
                break
            case 'error':
                toast.error(notification.title, toastOptions)
                break
            case 'warning':
                toast.warning(notification.title, toastOptions)
                break
            default:
                toast.info(notification.title, toastOptions)
        }
    }

    const markAsRead = async (notificationId: string) => {
        const notification = notifications.value.find(n => n.id === notificationId)
        if (notification && !notification.read_at) {
            const previousReadAt = notification.read_at
            notification.read_at = new Date().toISOString()

            router.post(`/notifications/${notificationId}/read`, {}, {
                preserveScroll: true,
                preserveState: true,
                only: [],
                onError: () => {
                    notification.read_at = previousReadAt
                    console.error('[Notifications] Erro ao marcar como lida')
                },
            })
        }
    }

    const markAllAsRead = async () => {
        const unread = notifications.value.filter(n => !n.read_at)
        const previousState = unread.map(n => ({ id: n.id, read_at: n.read_at }))

        unread.forEach(n => {
            n.read_at = new Date().toISOString()
        })

        router.post('/notifications/read-all', {}, {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onError: () => {
                previousState.forEach(state => {
                    const notification = notifications.value.find(n => n.id === state.id)
                    if (notification) {
                        notification.read_at = state.read_at
                    }
                })
                console.error('[Notifications] Erro ao marcar todas como lidas')
            },
        })
    }

    const removeNotification = async (notificationId: string) => {
        const notification = notifications.value.find(n => n.id === notificationId)
        if (!notification) return

        notifications.value = notifications.value.filter(n => n.id !== notificationId)

        router.delete(`/notifications/${notificationId}`, {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onError: () => {
                notifications.value.unshift(notification)
                console.error('[Notifications] Erro ao remover notificação')
            },
        })
    }

    const clearAll = async () => {
        const previousNotifications = [...notifications.value]
        notifications.value = []

        router.delete('/notifications', {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onError: () => {
                notifications.value = previousNotifications
                console.error('[Notifications] Erro ao limpar notificações')
            },
        })
    }

    const loadNotifications = async () => {
        try {
            const response = await fetch('/notifications', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            })

            if (!response.ok) return

            const data = await response.json()
            if (!data.notifications || !Array.isArray(data.notifications)) return

            notifications.value = data.notifications.map((n: any) => ({
                id: n.id,
                type: n.type,
                title: n.title,
                message: n.message,
                data: n.data,
                read_at: n.read_at,
                created_at: n.created_at,
                notification_id: n.notification_id,
                notification_type: n.notification_type,
            }))
        } catch (error) {
            console.error('[Notifications] Erro ao carregar:', error)
        }
    }

    // ========================================================================
    // Event Processing
    // ========================================================================

    /**
     * Processa qualquer evento recebido
     */
    const processEvent = (eventName: string, payload: EventPayload) => {
        // Busca handler específico
        const handler = findHandler(eventName)

        let notification: GlobalNotification | null

        if (handler) {
            notification = handler(eventName, payload)
        } else {
            // Usa normalização genérica
            notification = normalizeToNotification(eventName, payload)
        }

        if (notification) {
            addNotification(notification)
        }
    }

    /**
     * Processa atualizações de sync (entre abas)
     */
    const processSyncEvent = (payload: { action: string; notification_id?: string; unread_count?: number }) => {
        switch (payload.action) {
            case 'read':
                if (payload.notification_id) {
                    const notification = notifications.value.find(n => n.id === payload.notification_id)
                    if (notification && !notification.read_at) {
                        notification.read_at = new Date().toISOString()
                    }
                }
                break

            case 'read_all':
                notifications.value.forEach(n => {
                    if (!n.read_at) {
                        n.read_at = new Date().toISOString()
                    }
                })
                break

            case 'deleted':
                if (payload.notification_id) {
                    notifications.value = notifications.value.filter(n => n.id !== payload.notification_id)
                }
                break

            case 'cleared':
                notifications.value = []
                break
        }
    }

    // ========================================================================
    // Echo Listeners Setup
    // ========================================================================

    const userId = (page.props as any).auth?.user?.id
    const cleanupFunctions: (() => void)[] = []

    if (userId) {
        // Canal padrão do Laravel para notificações de model
        const modelChannel = `App.Models.User.${userId}`
        
        // Canal do usuário para eventos customizados
        const userChannel = `user.${userId}`
        const usersChannel = `users.${userId}`

        // Lista de eventos para escutar (genérico - captura tudo)
        const allEventPatterns = [
            // Laravel Notifications
            '.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated',
            '.notification.created',
            '.notification',
            // Eventos customizados comuns
            '.import.completed',
            '.export.completed',
            '.notification.updated',
            '.database.connection.failed',
        ]

        // Setup listeners para canal de model (App.Models.User.{id})
        allEventPatterns.forEach(eventName => {
            const { leaveChannel, listen } = useEcho(
                modelChannel,
                eventName,
                (payload: EventPayload) => processEvent(eventName, payload),
            )
            // Inicia o listener
            listen()
            cleanupFunctions.push(leaveChannel)
        })

        // Setup listeners para canal do usuário (user.{id})
        allEventPatterns.forEach(eventName => {
            const { leaveChannel, listen } = useEcho(
                userChannel,
                eventName,
                (payload: EventPayload) => processEvent(eventName, payload),
            )
            // Inicia o listener
            listen()
            cleanupFunctions.push(leaveChannel)
        })

        // Setup listeners para canal users.{id} (import/export)
        allEventPatterns.forEach(eventName => {
            const { leaveChannel, listen } = useEcho(
                usersChannel,
                eventName,
                (payload: EventPayload) => processEvent(eventName, payload),
            )
            // Inicia o listener
            listen()
            cleanupFunctions.push(leaveChannel)
        })

        // Listener especial para sync entre abas
        const { leaveChannel: leaveSyncChannel, listen: listenSync } = useEcho(
            userChannel,
            '.notification.updated',
            (payload: any) => processSyncEvent(payload),
        )
        listenSync()
        cleanupFunctions.push(leaveSyncChannel)
    }

    // ========================================================================
    // Lifecycle
    // ========================================================================

    onMounted(() => {
        loadNotifications()
        setGlobalNotificationHandler(addNotification)
    })

    onUnmounted(() => {
        cleanupFunctions.forEach(cleanup => cleanup())
    })

    return {
        // State
        notifications,
        unreadCount,
        isConnected,
        connectionStatus,
        
        // Actions
        addNotification,
        markAsRead,
        markAllAsRead,
        removeNotification,
        clearAll,
        loadNotifications,
        
        // Event processing (exposed for manual use)
        processEvent,
    }
}

// ============================================================================
// Global Notification Helper
// ============================================================================

let globalAddNotification: ((notification: GlobalNotification) => void) | null = null

export function setGlobalNotificationHandler(handler: (notification: GlobalNotification) => void) {
    globalAddNotification = handler
}

/**
 * Adiciona uma notificação de qualquer lugar do código
 * 
 * @example
 * ```ts
 * import { notify } from '@/composables/useGlobalNotifications'
 * 
 * notify('success', 'Sucesso!', 'Operação concluída')
 * notify('error', 'Erro', 'Algo deu errado', { error_code: 500 })
 * ```
 */
export function notify(
    type: 'info' | 'success' | 'warning' | 'error',
    title: string,
    message?: string,
    data?: Record<string, any>
) {
    if (!globalAddNotification) {
        console.warn('[Notifications] Handler global não configurado. Use useGlobalNotifications() primeiro.')
        return
    }

    const notification = createNotification(type, title, message, data)
    globalAddNotification(notification)
}