<template>
  <div class="file-upload-with-progress">
    <!-- File Input -->
    <div class="upload-input-container">
      <label
        :for="inputId"
        class="upload-label"
        :class="{ 'upload-label--dragging': isDragging }"
        @dragover.prevent="handleDragOver"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDrop"
      >
        <input
          :id="inputId"
          ref="fileInput"
          type="file"
          :accept="accept"
          :multiple="multiple"
          class="upload-input"
          @change="handleFileSelect"
          :disabled="disabled || uploading"
        />

        <div v-if="!uploading && !currentUpload" class="upload-placeholder">
          <svg
            class="upload-icon"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
            />
          </svg>
          <p class="upload-text">
            {{ placeholder || 'Arraste um arquivo ou clique para selecionar' }}
          </p>
          <p class="upload-hint">{{ hint }}</p>
        </div>

        <!-- Upload Progress -->
        <div v-else-if="uploading || isProcessing" class="upload-progress">
          <div class="progress-info">
            <div class="progress-header">
              <span class="progress-filename">{{ selectedFileName }}</span>
              <button
                v-if="uploading && !isProcessing"
                type="button"
                class="progress-cancel"
                @click.stop="handleCancel"
              >
                Cancelar
              </button>
            </div>

            <!-- Chunked Upload Progress -->
            <div v-if="uploading" class="progress-stage">
              <p class="progress-label">
                Enviando: {{ progress }}% ({{ currentChunk }}/{{ totalChunks }} chunks)
              </p>
              <div class="progress-bar-container">
                <div class="progress-bar" :style="{ width: `${progress}%` }"></div>
              </div>
            </div>

            <!-- Processing Progress -->
            <div v-if="currentUpload && isProcessing" class="progress-stage">
              <p class="progress-label">
                Processando: {{ currentUpload.progress }}%
              </p>
              <div class="progress-bar-container">
                <div
                  class="progress-bar progress-bar--processing"
                  :style="{ width: `${currentUpload.progress}%` }"
                ></div>
              </div>
            </div>

            <!-- Status Messages -->
            <p v-if="statusMessage" class="progress-status">{{ statusMessage }}</p>
          </div>

          <!-- Loading Spinner -->
          <div class="progress-spinner">
            <svg
              class="spinner-icon"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="spinner-track"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="spinner-path"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
          </div>
        </div>

        <!-- Completed Upload Preview -->
        <div v-else-if="currentUpload && isCompleted" class="upload-completed">
          <!-- Image Preview -->
          <div v-if="isImage && currentUpload.thumbnail_urls?.small" class="upload-preview">
            <img
              :src="currentUpload.thumbnail_urls.medium || currentUpload.thumbnail_urls.small"
              :alt="currentUpload.field_name"
              class="preview-image"
            />
            <div class="preview-overlay">
              <button type="button" class="preview-action" @click.stop="handleRemove">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- File Info (non-image) -->
          <div v-else class="upload-file-info">
            <svg
              class="file-icon"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            <div class="file-details">
              <p class="file-name">{{ selectedFileName }}</p>
              <p class="file-size">{{ formatFileSize(currentUpload.metadata?.file_size) }}</p>
            </div>
            <button type="button" class="file-remove" @click.stop="handleRemove">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="uploadError || (currentUpload && isFailed)" class="upload-error">
          <svg
            class="error-icon"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <p class="error-message">
            {{ uploadError || currentUpload?.error || 'Erro ao fazer upload' }}
          </p>
          <button type="button" class="error-retry" @click.stop="handleRetry">
            Tentar novamente
          </button>
        </div>
      </label>
    </div>

    <!-- Hidden input to store the file upload ID -->
    <input v-if="currentUpload?.id" type="hidden" :name="name" :value="currentUpload.id" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useChunkedUpload } from '../composables/useChunkedUpload'
import { useFileUploadProgress } from '../composables/useFileUploadProgress'

interface Props {
  name: string
  fieldName: string
  userId: string | number
  modelType?: string
  modelId?: string
  accept?: string
  multiple?: boolean
  disabled?: boolean
  placeholder?: string
  hint?: string
  maxSize?: number // MB
  chunkSize?: number // bytes
}

const props = withDefaults(defineProps<Props>(), {
  accept: '*',
  multiple: false,
  disabled: false,
  maxSize: 100, // 100MB default
  chunkSize: 5 * 1024 * 1024, // 5MB default
})

const emit = defineEmits<{
  (e: 'upload-start', file: File): void
  (e: 'upload-progress', progress: number): void
  (e: 'upload-complete', uploadId: string): void
  (e: 'upload-error', error: string): void
  (e: 'upload-cancelled'): void
}>()

// Refs
const fileInput = ref<HTMLInputElement>()
const isDragging = ref(false)
const selectedFile = ref<File | null>(null)
const selectedFileName = ref('')
const statusMessage = ref('')
const currentUploadId = ref<string | null>(null)

// Generate unique input ID
const inputId = computed(() => `upload-${props.fieldName}-${Math.random().toString(36).slice(2, 9)}`)

// Chunked Upload Composable
const {
  uploadFile,
  cancelUpload,
  uploading,
  progress,
  error: uploadError,
  state: uploadState,
  reset: resetUpload,
} = useChunkedUpload({
  chunkSize: props.chunkSize,
  onProgress: (prog) => {
    emit('upload-progress', prog)
  },
  onComplete: (uploadId) => {
    currentUploadId.value = uploadId
    statusMessage.value = 'Processando arquivo...'
    progressTracker.registerUpload(uploadId, props.fieldName, {
      status: 'processing',
      progress: 0,
    })
  },
  onError: (error) => {
    emit('upload-error', error.message)
  },
})

// Progress Tracker Composable
const progressTracker = useFileUploadProgress(props.userId, {
  onStatusChange: (upload) => {
    if (upload.id === currentUploadId.value) {
      if (upload.status === 'processing') {
        statusMessage.value = `Processando: ${upload.progress}%`
      } else if (upload.status === 'completed') {
        statusMessage.value = 'Upload concluído!'
        emit('upload-complete', upload.id)
      } else if (upload.status === 'failed') {
        statusMessage.value = ''
        emit('upload-error', upload.error || 'Falha ao processar arquivo')
      }
    }
  },
  onCompleted: (upload) => {
    console.log('Upload completed:', upload)
  },
  onFailed: (upload) => {
    console.error('Upload failed:', upload)
  },
})

// Computed
const currentChunk = computed(() => uploadState.value?.currentChunk || 0)
const totalChunks = computed(() => uploadState.value?.totalChunks || 0)

const currentUpload = computed(() => {
  if (!currentUploadId.value) return null
  return progressTracker.getUpload(currentUploadId.value)
})

const isProcessing = computed(() => currentUpload.value?.status === 'processing')
const isCompleted = computed(() => currentUpload.value?.status === 'completed')
const isFailed = computed(() => currentUpload.value?.status === 'failed')

const isImage = computed(() => {
  if (!selectedFile.value) return false
  return selectedFile.value.type.startsWith('image/')
})

// Methods
const handleFileSelect = (event: Event): void => {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (files && files.length > 0) {
    startUpload(files[0])
  }
}

const handleDragOver = (): void => {
  isDragging.value = true
}

const handleDragLeave = (): void => {
  isDragging.value = false
}

const handleDrop = (event: DragEvent): void => {
  isDragging.value = false
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    startUpload(files[0])
  }
}

const startUpload = async (file: File): Promise<void> => {
  // Validate file size
  if (props.maxSize && file.size > props.maxSize * 1024 * 1024) {
    emit('upload-error', `Arquivo muito grande. Tamanho máximo: ${props.maxSize}MB`)
    return
  }

  selectedFile.value = file
  selectedFileName.value = file.name
  statusMessage.value = 'Iniciando upload...'

  emit('upload-start', file)

  try {
    await uploadFile(file, props.fieldName, props.modelType, props.modelId)
  } catch (error: any) {
    console.error('Upload failed:', error)
  }
}

const handleCancel = async (): Promise<void> => {
  await cancelUpload()
  resetState()
  emit('upload-cancelled')
}

const handleRemove = (): void => {
  resetState()
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const handleRetry = (): void => {
  if (selectedFile.value) {
    resetUpload()
    startUpload(selectedFile.value)
  }
}

const resetState = (): void => {
  selectedFile.value = null
  selectedFileName.value = ''
  currentUploadId.value = null
  statusMessage.value = ''
  resetUpload()
}

const formatFileSize = (bytes?: number): string => {
  if (!bytes) return ''
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}
</script>

<style scoped>
.file-upload-with-progress {
  width: 100%;
}

.upload-input-container {
  position: relative;
}

.upload-label {
  display: block;
  width: 100%;
  min-height: 200px;
  border: 2px dashed #cbd5e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  background-color: #f7fafc;
}

.upload-label:hover {
  border-color: #4299e1;
  background-color: #edf2f7;
}

.upload-label--dragging {
  border-color: #4299e1;
  background-color: #bee3f8;
}

.upload-input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  overflow: hidden;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  text-align: center;
}

.upload-icon {
  width: 64px;
  height: 64px;
  color: #a0aec0;
  margin-bottom: 1rem;
}

.upload-text {
  font-size: 1rem;
  font-weight: 500;
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.upload-hint {
  font-size: 0.875rem;
  color: #718096;
}

.upload-progress {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.progress-info {
  flex: 1;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.progress-filename {
  font-weight: 600;
  color: #2d3748;
  font-size: 0.875rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.progress-cancel {
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  color: #e53e3e;
  background: transparent;
  border: 1px solid #e53e3e;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.progress-cancel:hover {
  background-color: #e53e3e;
  color: white;
}

.progress-stage {
  margin-bottom: 1rem;
}

.progress-label {
  font-size: 0.875rem;
  color: #4a5568;
  margin-bottom: 0.5rem;
}

.progress-bar-container {
  width: 100%;
  height: 8px;
  background-color: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background-color: #4299e1;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-bar--processing {
  background-color: #48bb78;
}

.progress-status {
  margin-top: 0.5rem;
  font-size: 0.75rem;
  color: #718096;
  font-style: italic;
}

.progress-spinner {
  display: flex;
  justify-content: center;
  align-items: center;
}

.spinner-icon {
  width: 48px;
  height: 48px;
  animation: spin 1s linear infinite;
}

.spinner-track {
  opacity: 0.25;
}

.spinner-path {
  opacity: 0.75;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.upload-completed {
  padding: 1rem;
}

.upload-preview {
  position: relative;
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
}

.preview-image {
  width: 100%;
  height: auto;
  display: block;
}

.preview-overlay {
  position: absolute;
  top: 0;
  right: 0;
  padding: 0.5rem;
}

.preview-action {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(0, 0, 0, 0.5);
  border: none;
  border-radius: 4px;
  color: white;
  cursor: pointer;
  transition: background-color 0.2s;
}

.preview-action:hover {
  background-color: rgba(0, 0, 0, 0.7);
}

.preview-action svg {
  width: 20px;
  height: 20px;
}

.upload-file-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background-color: #f7fafc;
  border-radius: 8px;
}

.file-icon {
  width: 40px;
  height: 40px;
  color: #4a5568;
  flex-shrink: 0;
}

.file-details {
  flex: 1;
  overflow: hidden;
}

.file-name {
  font-weight: 500;
  color: #2d3748;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-size {
  font-size: 0.75rem;
  color: #718096;
  margin-top: 0.25rem;
}

.file-remove {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: transparent;
  border: 1px solid #cbd5e0;
  border-radius: 4px;
  color: #718096;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.file-remove:hover {
  background-color: #e53e3e;
  border-color: #e53e3e;
  color: white;
}

.file-remove svg {
  width: 16px;
  height: 16px;
}

.upload-error {
  padding: 2rem;
  text-align: center;
}

.error-icon {
  width: 64px;
  height: 64px;
  color: #e53e3e;
  margin: 0 auto 1rem;
}

.error-message {
  font-size: 0.875rem;
  color: #742a2a;
  margin-bottom: 1rem;
}

.error-retry {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  color: white;
  background-color: #4299e1;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.error-retry:hover {
  background-color: #3182ce;
}
</style>
