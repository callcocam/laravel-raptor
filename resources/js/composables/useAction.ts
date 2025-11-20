import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { useNotifications } from '~/composables/useNotifications'

interface ActionConfig {
  url: string
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  actionType?: 'link' | 'api' | 'callback' | 'modal' | 'table'
  callback?: string
  confirm?: {
    title?: string
    message?: string
    confirmText?: string
    cancelText?: string
    confirmColor?: string
    requiresTypedConfirmation?: boolean
    typedConfirmationWord?: string
  }
  inertia?: {
    preserveScroll?: boolean
    preserveState?: boolean
    only?: string[]
  }
  successMessage?: string
  errorMessage?: string
  onSuccess?: (data: any) => void
  onError?: (error: any) => void
}

export function useAction() {
  const notifications = useNotifications()
  const isExecuting = ref(false)
  const error = ref<any>(null)
  const data = ref<any>(null)

  /**
   * Executa uma action com dados de formulário usando router moderno
   */
  const execute = async (
    action: ActionConfig,
    formData?: Record<string, any>
  ): Promise<boolean> => {
    isExecuting.value = true
    error.value = null
    data.value = null

    try {
      const options: any = {
        preserveState: action.inertia?.preserveState ?? true,
        preserveScroll: action.inertia?.preserveScroll ?? true, 
        onSuccess: (page: any) => {
          data.value = page
          if (action.successMessage) notifications.success(action.successMessage)
          if (action.onSuccess) action.onSuccess(page)
        },
        onError: (err: any) => {
          error.value = err
          if (action.errorMessage) notifications.error(action.errorMessage)
          else notifications.error('Ocorreu um erro ao executar a ação.')
          if (action.onError) action.onError(err)
        },
        onFinish: () => {
          isExecuting.value = false
        }
      }

      // Se houver arquivos, usa FormData
      let payload: Record<string, any> | FormData | undefined = formData
      if (formData && Object.values(formData).some(v => v instanceof File)) {
        const formDataPayload = new FormData()
        Object.entries(formData).forEach(([key, value]) => {
          if (value instanceof File) {
            formDataPayload.append(key, value)
          } else if (Array.isArray(value)) {
            value.forEach(item => {
              if (item instanceof File) formDataPayload.append(`${key}[]`, item)
              else formDataPayload.append(`${key}[]`, String(item))
            })
          } else if (value !== null && value !== undefined) {
            formDataPayload.append(key, String(value))
          }
        })
        payload = formDataPayload
      }
      console.log(payload, formData);
      // Executa usando router moderno do Inertia.js
      switch (action.method) {
        case 'GET':
          router.get(action.url, formData || {}, options)
          break
        case 'POST':
          router.post(action.url, payload || {}, options)
          break
        case 'PUT':
          router.put(action.url, payload || {}, options)
          break
        case 'PATCH':
          router.patch(action.url, payload || {}, options)
          break
        case 'DELETE':
          router.delete(action.url,  options )
          break
      }

      return error.value === null
    } catch (e) {
      error.value = e
      isExecuting.value = false
      return false
    }
  }

  /**
   * Executa uma action GET simples
   */
  const executeGet = async (url: string, params?: Record<string, any>) => {
    return execute({
      url,
      method: 'GET'
    }, params)
  }

  /**
   * Executa uma action POST simples
   */
  const executePost = async (url: string, data?: Record<string, any>) => {
    return execute({
      url,
      method: 'POST'
    }, data)
  }

  /**
   * Executa uma action DELETE com confirmação
   */
  const executeDelete = async (url: string, confirmed: boolean = false) => {
    if (!confirmed) {
      // Se não confirmado, retorna false para mostrar confirmação
      return false
    }
    return execute({
      url,
      method: 'DELETE',
      successMessage: 'Registro excluído com sucesso!'
    })
  }

  /**
   * Valida se a palavra digitada está correta
   */
  const validateTypedConfirmation = (
    typedWord: string,
    expectedWord: string
  ): boolean => {
    return typedWord.toUpperCase().trim() === expectedWord.toUpperCase().trim()
  }

  /**
   * Verifica se uma action requer confirmação por digitação
   */
  const requiresTypedConfirmation = (action: ActionConfig): boolean => {
    return action.confirm?.requiresTypedConfirmation === true
  }

  /**
   * Obtém a palavra esperada para confirmação
   */
  const getTypedConfirmationWord = (action: ActionConfig): string => {
    return action.confirm?.typedConfirmationWord || 'EXCLUIR'
  }

  /**
   * Cria um formulário Inertia com useForm para modal actions
   */
  const createForm = <T extends Record<string, any>>(initialData: T) => {
    return useForm(initialData)
  }

  /**
   * Executa callback action (função JavaScript registrada no window)
   */
  const executeCallback = (callbackName: string, ...args: any[]): boolean => {
    if (typeof window === 'undefined') {
      console.error('executeCallback can only be used in browser environment')
      return false
    }

    const callback = (window as any)[callbackName]

    if (typeof callback === 'function') {
      try {
        callback(...args)
        return true
      } catch (error) {
        console.error(`Error executing callback "${callbackName}":`, error)
        notifications.error(`Erro ao executar ação: ${callbackName}`)
        return false
      }
    } else {
      console.error(`Callback function "${callbackName}" not found on window object`)
      notifications.error(`Ação não encontrada: ${callbackName}`)
      return false
    }
  }

  return {
    execute,
    executeGet,
    executePost,
    executeDelete,
    validateTypedConfirmation,
    requiresTypedConfirmation,
    getTypedConfirmationWord,
    createForm,
    executeCallback,
    isExecuting: computed(() => isExecuting.value),
    error,
    data
  }
}
