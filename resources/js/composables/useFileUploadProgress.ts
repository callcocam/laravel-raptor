import { ref, computed, onUnmounted, Ref } from 'vue'

// Echo pode não estar disponível se WebSocket não estiver configurado
let Echo: any = null
try {
  Echo = (window as any).Echo
} catch (e) {
  console.warn('Laravel Echo not available. WebSocket features will be disabled.')
}

export interface FileUploadStatus {
  id: string
  status: 'pending' | 'uploading' | 'processing' | 'completed' | 'failed'
  progress: number
  field_name: string
  model_type?: string
  model_id?: string
  file_url?: string
  thumbnail_urls?: {
    small?: string
    medium?: string
    large?: string
  }
  final_path?: string
  metadata?: {
    width?: number
    height?: number
    aspect_ratio?: number
    file_size?: number
  }
  error?: string
}

export interface FileUploadProgressOptions {
  onStatusChange?: (upload: FileUploadStatus) => void
  onCompleted?: (upload: FileUploadStatus) => void
  onFailed?: (upload: FileUploadStatus) => void
}

export function useFileUploadProgress(
  userId: string | number,
  options: FileUploadProgressOptions = {}
) {
  const { onStatusChange, onCompleted, onFailed } = options

  // Map de uploads por ID
  const uploads: Ref<Map<string, FileUploadStatus>> = ref(new Map())

  // Estado de conexão
  const connected = ref(false)
  const error = ref<string | null>(null)

  /**
   * Registra um novo upload para tracking
   */
  const registerUpload = (uploadId: string, fieldName: string, initialStatus: Partial<FileUploadStatus> = {}): void => {
    const upload: FileUploadStatus = {
      id: uploadId,
      status: 'uploading',
      progress: 0,
      field_name: fieldName,
      ...initialStatus,
    }

    uploads.value.set(uploadId, upload)
  }

  /**
   * Atualiza o status de um upload
   */
  const updateUpload = (uploadData: Partial<FileUploadStatus> & { id: string }): void => {
    const existing = uploads.value.get(uploadData.id)
    if (!existing) {
      console.warn(`Upload ${uploadData.id} not found in tracked uploads`)
      return
    }

    const updated: FileUploadStatus = {
      ...existing,
      ...uploadData,
    }

    uploads.value.set(uploadData.id, updated)
    onStatusChange?.(updated)

    // Callbacks específicos por status
    if (updated.status === 'completed') {
      onCompleted?.(updated)
    } else if (updated.status === 'failed') {
      onFailed?.(updated)
    }
  }

  /**
   * Remove um upload do tracking
   */
  const removeUpload = (uploadId: string): void => {
    uploads.value.delete(uploadId)
  }

  /**
   * Obtém o status de um upload específico
   */
  const getUpload = (uploadId: string): FileUploadStatus | undefined => {
    return uploads.value.get(uploadId)
  }

  /**
   * Obtém uploads por field_name
   */
  const getUploadsByField = (fieldName: string): FileUploadStatus[] => {
    return Array.from(uploads.value.values()).filter(
      upload => upload.field_name === fieldName
    )
  }

  /**
   * Conecta ao canal privado do usuário para receber eventos
   */
  const connect = (): void => {
    if (!Echo) {
      console.warn('Laravel Echo not available. Real-time updates disabled. Use polling fallback.')
      connected.value = false
      return
    }

    try {
      const channel = Echo.private(`App.Models.User.${userId}`)

      channel
        .listen('.file-upload.processed', (event: FileUploadStatus) => {
          console.log('Received file-upload.processed event:', event)
          updateUpload(event)
        })
        .error((err: Error) => {
          console.error('Echo channel error:', err)
          error.value = err.message
          connected.value = false
        })

      // Marca como conectado
      connected.value = true
      error.value = null

      console.log(`Connected to upload progress channel for user ${userId}`)
    } catch (err: any) {
      console.error('Failed to connect to Echo:', err)
      error.value = err.message
      connected.value = false
    }
  }

  /**
   * Desconecta do canal
   */
  const disconnect = (): void => {
    if (!Echo) {
      return
    }

    try {
      Echo.leave(`App.Models.User.${userId}`)
      connected.value = false
      console.log(`Disconnected from upload progress channel for user ${userId}`)
    } catch (err: any) {
      console.error('Failed to disconnect from Echo:', err)
    }
  }

  /**
   * Polling fallback para verificar status de upload
   * Útil se WebSocket falhar
   */
  const pollStatus = async (uploadId: string): Promise<FileUploadStatus | null> => {
    try {
      const response = await fetch(`/api/upload/status/${uploadId}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      })

      if (!response.ok) {
        throw new Error(`Failed to fetch upload status: ${response.statusText}`)
      }

      const data = await response.json()

      if (data.success) {
        const upload: FileUploadStatus = {
          id: data.id,
          status: data.status,
          progress: data.progress,
          field_name: data.field_name,
          model_type: data.model_type,
          model_id: data.model_id,
          file_url: data.file_url,
          thumbnail_urls: data.thumbnail_urls,
          final_path: data.final_path,
          metadata: data.metadata,
          error: data.error,
        }

        updateUpload(upload)
        return upload
      }

      return null
    } catch (err: any) {
      console.error('Polling error:', err)
      return null
    }
  }

  /**
   * Inicia polling periódico para um upload
   * Útil como fallback se WebSocket não conectar
   */
  const startPolling = (uploadId: string, intervalMs: number = 2000): (() => void) => {
    const intervalId = setInterval(async () => {
      const upload = await pollStatus(uploadId)

      // Para o polling se completou ou falhou
      if (upload && (upload.status === 'completed' || upload.status === 'failed')) {
        clearInterval(intervalId)
      }
    }, intervalMs)

    // Retorna função para parar o polling
    return () => clearInterval(intervalId)
  }

  // Auto-conecta ao criar o composable
  connect()

  // Auto-desconecta ao destruir o componente
  onUnmounted(() => {
    disconnect()
  })

  return {
    // State
    uploads: computed(() => Array.from(uploads.value.values())),
    uploadsMap: computed(() => uploads.value),
    connected: computed(() => connected.value),
    error: computed(() => error.value),

    // Methods
    registerUpload,
    updateUpload,
    removeUpload,
    getUpload,
    getUploadsByField,
    connect,
    disconnect,
    pollStatus,
    startPolling,
  }
}
