import { ref, computed, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useEcho } from '@laravel/echo-vue'
import { toast } from 'vue-sonner'

export interface GlobalNotification {
    id: string
    type: 'info' | 'success' | 'warning' | 'error'
    title: string
    message?: string
    data?: Record<string, any>
    read_at?: string | null
    created_at: string
    // Dados específicos do Laravel Notification
    notification_id?: string
    notification_type?: string
}

/**
 * Helper para criar notificações de qualquer lugar do código
 * Útil para eventos customizados ou notificações manuais
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

export function useGlobalNotifications() {
    const page = usePage()
    const notifications = ref<GlobalNotification[]>([])
    const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length)
    const isConnected = ref(false)

    // Adiciona notificação à lista
    const addNotification = (notification: GlobalNotification) => {
        // Verifica se já existe (evita duplicatas)
        const exists = notifications.value.find(n => n.id === notification.id)
        if (exists) {
            return
        }

        // Adiciona no início da lista
        notifications.value.unshift(notification)

        // Mantém apenas as últimas 100 notificações
        if (notifications.value.length > 100) {
            notifications.value = notifications.value.slice(0, 100)
        }

        // Mostra toast automaticamente
        showToast(notification)
    }

    // Mostra toast para a notificação
    const showToast = (notification: GlobalNotification) => {
        const toastOptions = {
            description: notification.message || notification.title,
            duration: notification.type === 'error' ? 8000 : 5000,
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

    // Marca notificação como lida
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
                    // Reverte se falhar
                    notification.read_at = previousReadAt
                    console.error('[Global Notifications] Erro ao marcar como lida')
                }
            })
        }
    }

    // Marca todas como lidas
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
                // Reverte se falhar
                previousState.forEach(state => {
                    const notification = notifications.value.find(n => n.id === state.id)
                    if (notification) {
                        notification.read_at = state.read_at
                    }
                })
                console.error('[Global Notifications] Erro ao marcar todas como lidas')
            }
        })
    }

    // Remove notificação
    const removeNotification = async (notificationId: string) => {
        const notification = notifications.value.find(n => n.id === notificationId)
        if (!notification) return

        // Remove otimisticamente
        notifications.value = notifications.value.filter(n => n.id !== notificationId)

        router.delete(`/notifications/${notificationId}`, {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onError: () => {
                // Adiciona de volta se falhar
                notifications.value.unshift(notification)
                console.error('[Global Notifications] Erro ao remover notificação')
            }
        })
    }

    // Limpa todas as notificações
    const clearAll = async () => {
        const previousNotifications = [...notifications.value]
        notifications.value = []

        router.delete('/notifications', {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onError: () => {
                // Restaura se falhar
                notifications.value = previousNotifications
                console.error('[Global Notifications] Erro ao limpar notificações')
            }
        })
    }

    // Carrega notificações do backend
    const loadNotifications = async () => {
        try {
            const response = await fetch('/notifications', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            })

            if (!response.ok) {
                return
            }

            const data = await response.json()
            if (!data.notifications || !Array.isArray(data.notifications)) {
                return
            }
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
            console.error('[Global Notifications] Erro ao carregar notificações:', error)
        }
    }

    // Obtém userId do page props
    const userId = (page.props as any).auth?.user?.id
 
    // Conecta ao canal privado do usuário para notificações globais
    // O Laravel usa o evento 'Illuminate\\Notifications\\Events\\BroadcastNotificationCreated'
    // que é broadcastado como '.notification.created' quando uma notificação implementa ShouldBroadcast
    // O broadcastType() na notification permite customizar o nome do evento
    let listenNotification: (() => void) | null = null

    if (userId) {
        // O Laravel usa 'App.Models.User.{id}' como canal padrão para notificações
        const channelName = `App.Models.User.${userId}`

        // Tenta múltiplos nomes de eventos possíveis
        const possibleEventNames = [
            '.notification.created',
            '.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated',
            'notification.created',
            '.notification',
        ]

        const listeners: (() => void)[] = []

        possibleEventNames.forEach((eventName, index) => {
            const echoResult = useEcho<GlobalNotification>(
                channelName,
                eventName,
                (data: any) => {
                    isConnected.value = true

                    const notification: GlobalNotification = {
                        id: data.id || data.notification?.id || `notification-${Date.now()}-${Math.random()}`,
                        type: data.type || data.notification?.type || 'info',
                        title: data.title || data.notification?.title || data.message || data.notification?.message || 'Nova notificação',
                        message: data.message || data.notification?.message,
                        data: data.data || data.notification?.data || {},
                        read_at: null,
                        created_at: data.created_at || data.notification?.created_at || new Date().toISOString(),
                        notification_id: data.id || data.notification?.id,
                        notification_type: data.notification_type || data.notification?.type,
                    }

                    addNotification(notification)
                },
                [userId],
                'private'
            )

            if (echoResult.listen) {
                listeners.push(echoResult.listen)
            }
        })

        // Inicia todos os listeners
        listeners.forEach((listen, index) => {
            try {
                listen()
            } catch (error) {
                console.error('[Global Notifications] Erro ao iniciar listener:', error)
            }
        })

        // Eventos de importação
        const importExportChannel = `users.${userId}`
        const importEventNames = ['.import.completed', 'import.completed']
        const importListeners: (() => void)[] = []

        importEventNames.forEach((eventName) => {
            const importEchoResult = useEcho<{
                type: 'import'
                model: string
                total: number
                successful: number
                failed: number
                fileName: string | null
                message: string
                timestamp: string
            }>(
                importExportChannel,
                eventName,
                (data: any) => {
                    isConnected.value = true

                    const notification: GlobalNotification = {
                        id: `import-${Date.now()}-${Math.random()}`,
                        type: data.failed > 0 ? 'warning' : 'success',
                        title: data.message || 'Importação concluída',
                        message: data.fileName ? `Arquivo: ${data.fileName}` : undefined,
                        data: {
                            type: 'import',
                            model: data.model,
                            total: data.total,
                            successful: data.successful,
                            failed: data.failed,
                        },
                        read_at: null,
                        created_at: data.timestamp || new Date().toISOString(),
                    }

                    addNotification(notification)
                },
                [userId],
                'private'
            )

            if (importEchoResult.listen) {
                importListeners.push(importEchoResult.listen)
            }
        })

        importListeners.forEach((listen) => {
            try {
                listen()
            } catch (error) {
                console.error('[Global Notifications] Erro ao iniciar import listener:', error)
            }
        })

        // Eventos de exportação
        const exportEventNames = ['.export.completed', 'export.completed']
        const exportListeners: (() => void)[] = []

        exportEventNames.forEach((eventName) => {
            const exportEchoResult = useEcho<{
                type: 'export'
                model: string
                total: number
                filePath: string
                fileName: string | null
                downloadUrl: string
                message: string
                timestamp: string
            }>(
                importExportChannel,
                eventName,
                (data: any) => {
                    isConnected.value = true

                    const notification: GlobalNotification = {
                        id: `export-${Date.now()}-${Math.random()}`,
                        type: 'success',
                        title: data.message || 'Exportação concluída',
                        message: 'Clique para fazer o download',
                        data: {
                            type: 'export',
                            model: data.model,
                            total: data.total,
                            downloadUrl: data.downloadUrl,
                            fileName: data.fileName,
                            action: 'download',
                        },
                        read_at: null,
                        created_at: data.timestamp || new Date().toISOString(),
                    }

                    addNotification(notification)

                    toast.success(data.message || 'Exportação concluída', {
                        description: 'Clique para fazer o download',
                        duration: 10000,
                        action: {
                            label: 'Download',
                            onClick: () => {
                                window.location.href = data.downloadUrl
                            },
                        },
                    })
                },
                [userId],
                'private'
            )

            if (exportEchoResult.listen) {
                exportListeners.push(exportEchoResult.listen)
            }
        })

        exportListeners.forEach((listen) => {
            try {
                listen()
            } catch (error) {
                console.error('[Global Notifications] Erro ao iniciar export listener:', error)
            }
        })

        // Eventos de atualização de notificações (sincroniza entre abas)
        const updateChannelName = `user.${userId}`
        
        const updateEchoResult = useEcho<{ action: string; notification_id?: string; unread_count: number }>(
            updateChannelName,
            '.notification.updated',
            (data: any) => {
                switch (data.action) {
                    case 'read':
                        if (data.notification_id) {
                            const notification = notifications.value.find(n => n.id === data.notification_id)
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
                        if (data.notification_id) {
                            notifications.value = notifications.value.filter(n => n.id !== data.notification_id)
                        }
                        break

                    case 'cleared':
                        notifications.value = []
                        break
                }
            },
            [userId],
            'private'
        )

        if (updateEchoResult.listen) {
            updateEchoResult.listen()
        }

        // Eventos de erro de conexão de banco de dados
        const dbErrorChannelName = `user.${userId}`
        const dbErrorEventNames = ['database.connection.failed', '.database.connection.failed']
        const dbErrorListeners: (() => void)[] = []

        dbErrorEventNames.forEach((eventName) => {
            const dbErrorEchoResult = useEcho<{
                database: string
                message: string
                is_database_not_found: boolean
                resolution_steps: string[]
                timestamp: string
            }>(
                dbErrorChannelName,
                eventName,
                (data: any) => {
                    isConnected.value = true

                    const notification: GlobalNotification = {
                        id: `db-error-${Date.now()}-${Math.random()}`,
                        type: 'error',
                        title: 'Erro de Conexão com Banco de Dados',
                        message: data.message || `Não foi possível conectar ao banco de dados '${data.database}'.`,
                        data: {
                            database: data.database,
                            is_database_not_found: data.is_database_not_found,
                            resolution_steps: data.resolution_steps || [],
                            timestamp: data.timestamp,
                        },
                        read_at: null,
                        created_at: data.timestamp || new Date().toISOString(),
                    }

                    addNotification(notification)
                },
                [userId],
                'private'
            )

            if (dbErrorEchoResult.listen) {
                dbErrorListeners.push(dbErrorEchoResult.listen)
            }
        })

        dbErrorListeners.forEach((listen) => {
            try {
                listen()
            } catch (error) {
                console.error('[Global Notifications] Erro ao iniciar database error listener:', error)
            }
        })

        // Listener genérico para eventos não tratados especificamente
        const genericChannelName = `user.${userId}`
        const genericEventNames = ['.notification', 'notification', '.notification.*', '*.notification']
        const genericListeners: (() => void)[] = []

        genericEventNames.forEach((eventName) => {
            const genericEchoResult = useEcho<any>(
                genericChannelName,
                eventName,
                (data: any) => {
                    if (data && (data.type || data.title || data.message || data.notification)) {
                        isConnected.value = true

                        const normalizedData = data.notification || data

                        const notification: GlobalNotification = {
                            id: normalizedData.id || data.id || `notification-${Date.now()}-${Math.random()}`,
                            type: normalizedData.type || data.type || 'info',
                            title: normalizedData.title || data.title || normalizedData.message || data.message || 'Nova notificação',
                            message: normalizedData.message || data.message || normalizedData.description || data.description || normalizedData.body || data.body,
                            data: normalizedData.data || data.data || normalizedData.metadata || data.metadata || {},
                            read_at: null,
                            created_at: normalizedData.created_at || data.created_at || normalizedData.timestamp || data.timestamp || new Date().toISOString(),
                            notification_id: normalizedData.id || data.id,
                            notification_type: normalizedData.notification_type || data.notification_type || normalizedData.type || data.type,
                        }

                        addNotification(notification)
                    }
                },
                [userId],
                'private'
            )

            if (genericEchoResult.listen) {
                genericListeners.push(genericEchoResult.listen)
            }
        })

        genericListeners.forEach((listen) => {
            try {
                listen()
            } catch (error) {
                console.error('[Global Notifications] Erro ao iniciar generic listener:', error)
            }
        })
    }

    // Carrega notificações existentes ao montar
    onMounted(() => {
        loadNotifications()
        setGlobalNotificationHandler(addNotification)
    })

    return {
        notifications,
        unreadCount,
        isConnected,
        addNotification,
        markAsRead,
        markAllAsRead,
        removeNotification,
        clearAll,
        loadNotifications,
    }
}

/**
 * Função helper para adicionar notificações de qualquer lugar do código
 * 
 * @example
 * ```ts
 * import { notify } from '@/composables/useGlobalNotifications'
 * 
 * // Notificação simples
 * notify('success', 'Sucesso!', 'Operação concluída com sucesso')
 * 
 * // Notificação com dados extras
 * notify('error', 'Erro', 'Algo deu errado', { error_code: 500 })
 * ```
 */
let globalAddNotification: ((notification: GlobalNotification) => void) | null = null

export function setGlobalNotificationHandler(handler: (notification: GlobalNotification) => void) {
    globalAddNotification = handler
}

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

