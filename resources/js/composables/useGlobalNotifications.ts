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
        if (exists) return

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

    console.log('[Global Notifications] Inicializando com userId:', userId)

    // Conecta ao canal privado do usuário para notificações globais
    // O Laravel usa o evento 'Illuminate\\Notifications\\Events\\BroadcastNotificationCreated'
    // que é broadcastado como '.notification.created' quando uma notificação implementa ShouldBroadcast
    // O broadcastType() na notification permite customizar o nome do evento
    let listenNotification: (() => void) | null = null
    
    if (userId) {
        console.log('[Global Notifications] Configurando listeners para userId:', userId)
        // O Laravel usa 'App.Models.User.{id}' como canal padrão para notificações
        // Não 'user.{id}' como usamos para eventos customizados
        const channelName = `App.Models.User.${userId}`
        
        // Tenta múltiplos nomes de eventos possíveis
        // O Laravel pode usar diferentes nomes dependendo da versão e configuração
        const possibleEventNames = [
            '.notification.created',  // Com broadcastType() customizado
            '.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', // Evento padrão completo
            'notification.created',   // Sem o ponto inicial
            '.notification',           // Nome alternativo
        ]

        // Cria listeners para todos os eventos possíveis
        const listeners: (() => void)[] = []

        console.log('[Global Notifications] Criando listeners para canal:', channelName)
        console.log('[Global Notifications] Eventos possíveis:', possibleEventNames)

        possibleEventNames.forEach(eventName => {
            const echoResult = useEcho<GlobalNotification>(
                channelName,
                eventName,
                (data: any) => {
                    console.log('[Global Notifications] 🔔 Evento recebido no canal', channelName, 'evento:', eventName, 'dados:', data)
                    isConnected.value = true
                    
                    // O Laravel envia a notificação através do BroadcastMessage
                    // O formato pode variar, então tentamos diferentes estruturas
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
        console.log('[Global Notifications] Iniciando', listeners.length, 'listeners para notificações gerais')
        listeners.forEach((listen, index) => {
            try {
                listen()
                console.log('[Global Notifications] ✅ Listener', index + 1, 'iniciado')
            } catch (error) {
                console.error('[Global Notifications] ❌ Erro ao iniciar listener', index + 1, ':', error)
            }
        })

        // Escuta eventos de Import/Export no canal users.{userId}
        const importExportChannel = `users.${userId}`
        console.log('[Global Notifications] Configurando listeners Import/Export no canal:', importExportChannel)
        
        // Import completed
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
            '.import.completed',
            (data: any) => {
                console.log('[Global Notifications] 📥 IMPORT COMPLETED recebido! Dados:', data)
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
            try {
                importEchoResult.listen()
                console.log('[Global Notifications] ✅ Listener IMPORT configurado no canal:', importExportChannel)
            } catch (error) {
                console.error('[Global Notifications] ❌ Erro ao escutar import.completed:', error)
            }
        } else {
            console.warn('[Global Notifications] ⚠️ importEchoResult.listen não disponível')
        }
        
        // Export completed
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
            '.export.completed',
            (data: any) => {
                console.log('[Global Notifications] 📤 EXPORT COMPLETED recebido! Dados:', data)
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
                
                // Mostra toast com ação de download
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
            try {
                exportEchoResult.listen()
                console.log('[Global Notifications] ✅ Listener EXPORT configurado no canal:', importExportChannel)
            } catch (error) {
                console.error('[Global Notifications] ❌ Erro ao escutar export.completed:', error)
            }
        } else {
            console.warn('[Global Notifications] ⚠️ exportEchoResult.listen não disponível')
        }

        // Escuta eventos de atualização de notificações (marcar como lida, deletar, etc.)
        // Isso sincroniza as mudanças entre diferentes abas
        const updateChannelName = `user.${userId}`
        const updateEchoResult = useEcho<{ action: string; notification_id?: string; unread_count: number }>(
            updateChannelName,
            '.notification.updated',
            (data: any) => {
                switch (data.action) {
                    case 'read':
                        // Marca uma notificação específica como lida
                        if (data.notification_id) {
                            const notification = notifications.value.find(n => n.id === data.notification_id)
                            if (notification && !notification.read_at) {
                                notification.read_at = new Date().toISOString()
                            }
                        }
                        break

                    case 'read_all':
                        // Marca todas as notificações como lidas
                        notifications.value.forEach(n => {
                            if (!n.read_at) {
                                n.read_at = new Date().toISOString()
                            }
                        })
                        break

                    case 'deleted':
                        // Remove uma notificação específica
                        if (data.notification_id) {
                            notifications.value = notifications.value.filter(n => n.id !== data.notification_id)
                        }
                        break

                    case 'cleared':
                        // Remove todas as notificações
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

        // Escuta evento de falha de conexão de banco de dados
        // Este evento não é uma notificação salva no banco, é apenas broadcast
        const dbErrorChannelName = `user.${userId}`
        
        // Tenta ambos os formatos (com e sem ponto inicial)
        const dbErrorEventNames = [
            'database.connection.failed',  // Nome exato do broadcastAs()
            '.database.connection.failed', // Com prefixo (alguns broadcasters adicionam)
        ]

        const dbErrorListeners: (() => void)[] = []

        dbErrorEventNames.forEach(eventName => {
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
                    
                    // Cria uma notificação temporária (não salva no banco)
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

        // Inicia todos os listeners
        dbErrorListeners.forEach(listen => {
            try {
                listen()
            } catch (error) {
                // Silenciosamente ignora erros de listener
                console.warn('[Global Notifications] Erro ao escutar evento de erro de banco:', error)
            }
        })

        // Listener genérico para qualquer evento de notificação no canal do usuário
        // Captura eventos que não foram especificamente tratados acima
        // Formato esperado: { type, title, message, data?, ... }
        const genericChannelName = `user.${userId}`
        
        // Lista de eventos genéricos para escutar
        // Qualquer evento que tenha type, title e/ou message será tratado como notificação
        const genericEventNames = [
            '.notification',           // Evento genérico de notificação
            'notification',            // Sem prefixo
            '.notification.*',         // Qualquer evento que comece com notification
            '*.notification',          // Qualquer evento que termine com notification
        ]

        const genericListeners: (() => void)[] = []

        genericEventNames.forEach(eventName => {
            const genericEchoResult = useEcho<any>(
                genericChannelName,
                eventName,
                (data: any) => {
                    // Verifica se tem os campos mínimos para ser uma notificação
                    // Aceita qualquer evento que tenha type, title ou message
                    if (data && (data.type || data.title || data.message || data.notification)) {
                        isConnected.value = true
                        
                        // Normaliza dados (pode vir em diferentes formatos)
                        const normalizedData = data.notification || data
                        
                        // Cria notificação genérica
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

        // Inicia todos os listeners genéricos
        genericListeners.forEach(listen => {
            try {
                listen()
            } catch (error) {
                console.warn('[Global Notifications] Erro ao escutar eventos genéricos:', error)
            }
        })
    }

    // Carrega notificações existentes ao montar
    onMounted(() => {
        console.log('[Global Notifications] Componente montado, carregando notificações...')
        loadNotifications()
        // Registra o handler global para uso em qualquer lugar
        setGlobalNotificationHandler(addNotification)
        console.log('[Global Notifications] Conexão WebSocket status:', isConnected.value)
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

