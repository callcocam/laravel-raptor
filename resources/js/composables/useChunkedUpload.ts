import { ref, computed, Ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios, { AxiosProgressEvent } from 'axios'

export interface ChunkedUploadOptions {
  chunkSize?: number // Tamanho do chunk em bytes (padrão: 5MB)
  maxRetries?: number // Número máximo de tentativas por chunk
  parallelUploads?: number // Número de chunks para upload simultâneo
  onProgress?: (progress: number) => void
  onChunkProgress?: (chunkIndex: number, progress: number) => void
  onComplete?: (fileUploadId: string) => void
  onError?: (error: Error) => void
}

export interface UploadState {
  uploading: boolean
  progress: number
  currentChunk: number
  totalChunks: number
  error: string | null
  uploadId: string | null
  fileUploadId: string | null
}

export function useChunkedUpload(options: ChunkedUploadOptions = {}) {
  const {
    chunkSize = 5 * 1024 * 1024, // 5MB
    maxRetries = 3,
    parallelUploads = 3,
    onProgress,
    onChunkProgress,
    onComplete,
    onError,
  } = options

  const state: Ref<UploadState> = ref({
    uploading: false,
    progress: 0,
    currentChunk: 0,
    totalChunks: 0,
    error: null,
    uploadId: null,
    fileUploadId: null,
  })

  const abortControllers: Map<number, AbortController> = new Map()
  let uploadCancelled = false

  /**
   * Gera um ID único para o upload
   */
  const generateUploadId = (): string => {
    return `${Date.now()}-${Math.random().toString(36).substring(7)}`
  }

  /**
   * Divide o arquivo em chunks
   */
  const createChunks = (file: File): Blob[] => {
    const chunks: Blob[] = []
    let start = 0

    while (start < file.size) {
      const end = Math.min(start + chunkSize, file.size)
      chunks.push(file.slice(start, end))
      start = end
    }

    return chunks
  }

  /**
   * Faz upload de um chunk individual
   */
  const uploadChunk = async (
    chunk: Blob,
    chunkIndex: number,
    totalChunks: number,
    uploadId: string,
    originalName: string,
    fieldName: string,
    modelType?: string,
    modelId?: string,
    retryCount = 0
  ): Promise<void> => {
    if (uploadCancelled) {
      throw new Error('Upload cancelled')
    }

    const formData = new FormData()
    formData.append('file', chunk)
    formData.append('chunk_index', chunkIndex.toString())
    formData.append('total_chunks', totalChunks.toString())
    formData.append('upload_id', uploadId)
    formData.append('original_name', originalName)
    formData.append('field_name', fieldName)

    if (modelType) formData.append('model_type', modelType)
    if (modelId) formData.append('model_id', modelId)

    const controller = new AbortController()
    abortControllers.set(chunkIndex, controller)

    try {
      // Obtém o CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

      await axios.post('/api/upload/chunk', formData, {
        signal: controller.signal,
        headers: {
          'Content-Type': 'multipart/form-data',
          'X-CSRF-TOKEN': csrfToken || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        withCredentials: true,
        onUploadProgress: (progressEvent: AxiosProgressEvent) => {
          if (progressEvent.total) {
            const chunkProgress = (progressEvent.loaded / progressEvent.total) * 100
            onChunkProgress?.(chunkIndex, chunkProgress)
          }
        },
      })

      abortControllers.delete(chunkIndex)
    } catch (error: any) {
      abortControllers.delete(chunkIndex)

      // Se foi cancelado, não tenta retry
      if (axios.isCancel(error) || uploadCancelled) {
        throw new Error('Upload cancelled')
      }

      // Retry se não excedeu o máximo
      if (retryCount < maxRetries) {
        console.warn(`Retrying chunk ${chunkIndex}, attempt ${retryCount + 1}`)
        await new Promise(resolve => setTimeout(resolve, 1000 * (retryCount + 1))) // Backoff
        return uploadChunk(
          chunk,
          chunkIndex,
          totalChunks,
          uploadId,
          originalName,
          fieldName,
          modelType,
          modelId,
          retryCount + 1
        )
      }

      throw error
    }
  }

  /**
   * Faz upload de todos os chunks
   */
  const uploadChunks = async (
    chunks: Blob[],
    uploadId: string,
    originalName: string,
    fieldName: string,
    modelType?: string,
    modelId?: string
  ): Promise<void> => {
    const totalChunks = chunks.length
    let completedChunks = 0

    // Upload em paralelo (limitado por parallelUploads)
    const queue = chunks.map((chunk, index) => ({ chunk, index }))
    const inProgress: Promise<void>[] = []

    while (queue.length > 0 || inProgress.length > 0) {
      // Inicia novos uploads até atingir o limite
      while (inProgress.length < parallelUploads && queue.length > 0) {
        const { chunk, index } = queue.shift()!

        const promise = uploadChunk(
          chunk,
          index,
          totalChunks,
          uploadId,
          originalName,
          fieldName,
          modelType,
          modelId
        ).then(() => {
          completedChunks++
          state.value.currentChunk = completedChunks

          // Calcula progresso geral
          const progress = (completedChunks / totalChunks) * 100
          state.value.progress = Math.round(progress)
          onProgress?.(state.value.progress)
        })

        inProgress.push(promise)
      }

      // Aguarda pelo menos um upload completar
      if (inProgress.length > 0) {
        await Promise.race(inProgress)
        // Remove promises completadas
        for (let i = inProgress.length - 1; i >= 0; i--) {
          const settled = await Promise.race([
            inProgress[i].then(() => true),
            Promise.resolve(false)
          ])
          if (settled) {
            inProgress.splice(i, 1)
          }
        }
      }
    }
  }

  /**
   * Finaliza o upload
   */
  const completeUpload = async (
    uploadId: string,
    totalChunks: number,
    originalName: string
  ): Promise<string> => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

    const response = await axios.post('/api/upload/complete', {
      upload_id: uploadId,
      total_chunks: totalChunks,
      original_name: originalName,
    }, {
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'X-Requested-With': 'XMLHttpRequest',
      },
      withCredentials: true,
    })

    return response.data.file_upload_id
  }

  /**
   * Faz upload de um arquivo
   */
  const uploadFile = async (
    file: File,
    fieldName: string,
    modelType?: string,
    modelId?: string
  ): Promise<string> => {
    try {
      uploadCancelled = false
      state.value.uploading = true
      state.value.progress = 0
      state.value.currentChunk = 0
      state.value.error = null
      state.value.uploadId = generateUploadId()
      state.value.fileUploadId = null

      const chunks = createChunks(file)
      state.value.totalChunks = chunks.length

      console.log(`Starting chunked upload: ${chunks.length} chunks`)

      // Upload dos chunks
      await uploadChunks(
        chunks,
        state.value.uploadId,
        file.name,
        fieldName,
        modelType,
        modelId
      )

      // Finaliza o upload
      const fileUploadId = await completeUpload(
        state.value.uploadId,
        chunks.length,
        file.name
      )

      state.value.fileUploadId = fileUploadId
      state.value.uploading = false
      onComplete?.(fileUploadId)

      return fileUploadId
    } catch (error: any) {
      state.value.error = error.message || 'Upload failed'
      state.value.uploading = false
      onError?.(error)
      throw error
    }
  }

  /**
   * Cancela o upload em progresso
   */
  const cancelUpload = async (): Promise<void> => {
    if (!state.value.uploading || !state.value.uploadId) {
      return
    }

    uploadCancelled = true

    // Cancela todos os chunks em progresso
    abortControllers.forEach(controller => controller.abort())
    abortControllers.clear()

    // Notifica o servidor
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

      await axios.post('/api/upload/cancel', {
        upload_id: state.value.uploadId,
      }, {
        headers: {
          'X-CSRF-TOKEN': csrfToken || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        withCredentials: true,
      })
    } catch (error) {
      console.error('Failed to cancel upload on server:', error)
    }

    state.value.uploading = false
    state.value.error = 'Upload cancelled'
    state.value.uploadId = null
  }

  /**
   * Reseta o estado
   */
  const reset = (): void => {
    state.value = {
      uploading: false,
      progress: 0,
      currentChunk: 0,
      totalChunks: 0,
      error: null,
      uploadId: null,
      fileUploadId: null,
    }
    uploadCancelled = false
    abortControllers.clear()
  }

  return {
    // State
    state: computed(() => state.value),
    uploading: computed(() => state.value.uploading),
    progress: computed(() => state.value.progress),
    error: computed(() => state.value.error),
    fileUploadId: computed(() => state.value.fileUploadId),

    // Methods
    uploadFile,
    cancelUpload,
    reset,
  }
}
