import { ref, computed } from 'vue'
import { toast } from 'vue-sonner'
import type { ExternalToast } from 'vue-sonner'

/**
 * Gera um UUID v4 compatível com todos os navegadores
 */
const generateUUID = (): string => {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }

  // Fallback para navegadores antigos
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = Math.random() * 16 | 0
    const v = c === 'x' ? r : (r & 0x3 | 0x8)
    return v.toString(16)
  })
}

export interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  timestamp: Date
  read: boolean
  action?: {
    label: string
    onClick: () => void
  }
  metadata?: Record<string, any>
}

interface ToastOptions extends ExternalToast {
  icon?: any
}

// Estado global de notificações
const notifications = ref<Notification[]>([])
const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

export function useNotifications() {

  /**
   * Adiciona uma notificação ao centro de notificações
   */
  const addNotification = (notification: Omit<Notification, 'id' | 'timestamp' | 'read'>): Notification => {
    const newNotification: Notification = {
      ...notification,
      id: generateUUID(),
      timestamp: new Date(),
      read: false
    }

    notifications.value.unshift(newNotification)

    // Limita a 100 notificações
    if (notifications.value.length > 100) {
      notifications.value = notifications.value.slice(0, 100)
    }

    // Salva no localStorage
    saveToLocalStorage()

    return newNotification
  }

  /**
   * Marca uma notificação como lida
   */
  const markAsRead = (id: string) => {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
      notification.read = true
      saveToLocalStorage()
    }
  }

  /**
   * Marca todas as notificações como lidas
   */
  const markAllAsRead = () => {
    notifications.value.forEach(n => n.read = true)
    saveToLocalStorage()
  }

  /**
   * Remove uma notificação
   */
  const removeNotification = (id: string) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
      saveToLocalStorage()
    }
  }

  /**
   * Limpa todas as notificações
   */
  const clearAll = () => {
    notifications.value = []
    saveToLocalStorage()
  }

  /**
   * Salva notificações no localStorage
   */
  const saveToLocalStorage = () => {
    try {
      localStorage.setItem('notifications', JSON.stringify(notifications.value))
    } catch (e) {
      console.error('Failed to save notifications:', e)
    }
  }

  /**
   * Carrega notificações do localStorage
   */
  const loadFromLocalStorage = () => {
    try {
      const stored = localStorage.getItem('notifications')
      if (stored) {
        const parsed = JSON.parse(stored)
        notifications.value = parsed.map((n: any) => ({
          ...n,
          timestamp: new Date(n.timestamp)
        }))
      }
    } catch (e) {
      console.error('Failed to load notifications:', e)
    }
  }

  // ========== Métodos de Toast Aprimorados ==========

  /**
   * Toast de sucesso
   */
  const success = (message: string, options?: ToastOptions) => {
    const notification = addNotification({
      type: 'success',
      title: 'Sucesso',
      message,
    })

    toast.success(message, {
      ...options,
      action: options?.action,
    })

    return notification
  }

  /**
   * Toast de erro
   */
  const error = (message: string, options?: ToastOptions) => {
    const notification = addNotification({
      type: 'error',
      title: 'Erro',
      message,
    })

    toast.error(message, {
      ...options,
      action: options?.action,
    })

    return notification
  }

  /**
   * Toast de aviso
   */
  const warning = (message: string, options?: ToastOptions) => {
    const notification = addNotification({
      type: 'warning',
      title: 'Aviso',
      message,
    })

    toast.warning(message, {
      ...options,
      action: options?.action,
    })

    return notification
  }

  /**
   * Toast de informação
   */
  const info = (message: string, options?: ToastOptions) => {
    const notification = addNotification({
      type: 'info',
      title: 'Informação',
      message,
    })

    toast.info(message, {
      ...options,
      action: options?.action,
    })

    return notification
  }

  /**
   * Toast com loading (promessa)
   */
  const promise = <T>(
    promise: Promise<T>,
    options: {
      loading: string
      success: string | ((data: T) => string)
      error: string | ((error: any) => string)
    }
  ) => {
    return toast.promise(promise, options)
  }

  /**
   * Toast customizado
   */
  const custom = (message: string, options?: ToastOptions) => {
    return toast(message, options)
  }

  // Carrega notificações do localStorage ao inicializar
  loadFromLocalStorage()

  return {
    // Estado
    notifications: computed(() => notifications.value),
    unreadCount,

    // Gerenciamento de notificações
    addNotification,
    markAsRead,
    markAllAsRead,
    removeNotification,
    clearAll,
    loadFromLocalStorage,

    // Toasts aprimorados
    success,
    error,
    warning,
    info,
    promise,
    custom,

    // Alias para compatibilidade com código existente
    toast: {
      success,
      error,
      warning,
      info,
      promise,
      custom
    }
  }
}
