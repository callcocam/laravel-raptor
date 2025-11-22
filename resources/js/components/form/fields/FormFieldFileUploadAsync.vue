<!--
 * FormFieldFileUploadAsync - Async file upload field with chunking and real-time progress
 *
 * Uses FileUploadWithProgress component for chunked uploads with WebSocket notifications
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <FileUploadWithProgress
      v-if="column.userId"
      :name="column.name"
      :field-name="column.name"
      :user-id="column.userId"
      :model-type="column.modelType"
      :model-id="column.modelId"
      :accept="acceptAttribute"
      :multiple="column.multiple || false"
      :disabled="column.disabled || false"
      :placeholder="column.placeholder"
      :hint="hintText"
      :max-size="column.maxSize"
      :chunk-size="column.chunkSize"
      :model-value="modelValue"
      @update:model-value="handleUpdate"
      @upload-start="handleUploadStart"
      @upload-progress="handleUploadProgress"
      @upload-complete="handleUploadComplete"
      @upload-error="handleUploadError"
      @upload-cancelled="handleUploadCancelled"
    />
    <div v-else class="p-4 bg-destructive/10 text-destructive rounded-md text-sm">
      Erro: userId não configurado para upload assíncrono
    </div>

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import FileUploadWithProgress from '../../FileUploadWithProgress.vue'

interface FormColumn {
  name: string
  label?: string
  required?: boolean
  disabled?: boolean
  acceptedFileTypes?: string[]
  maxSize?: number
  multiple?: boolean
  tooltip?: string
  helpText?: string
  hint?: string
  placeholder?: string
  // Async specific props
  async?: boolean
  chunkSize?: number
  userId?: string | number
  modelType?: string
  modelId?: string
}

interface Props {
  column: FormColumn
  modelValue?: string | string[] | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | string[] | null): void
}>()

const localErrorMessage = ref('')
const uploadProgress = ref(0)
const isUploading = ref(false)

const hasError = computed(() => !!props.error || !!localErrorMessage.value)

const errorArray = computed(() => {
  const errors = []

  if (props.error) {
    if (Array.isArray(props.error)) {
      errors.push(...props.error.map(msg => ({ message: msg })))
    } else {
      errors.push({ message: props.error })
    }
  }

  if (localErrorMessage.value) {
    errors.push({ message: localErrorMessage.value })
  }

  return errors
})

const acceptAttribute = computed(() => {
  if (!props.column.acceptedFileTypes || props.column.acceptedFileTypes.length === 0) {
    return '*'
  }
  return props.column.acceptedFileTypes.join(',')
})

const acceptedTypesText = computed(() => {
  if (!props.column.acceptedFileTypes || props.column.acceptedFileTypes.length === 0) {
    return null
  }
  return props.column.acceptedFileTypes.map(type => type.replace('.', '').toUpperCase()).join(', ')
})

const hintText = computed(() => {
  const parts = []

  if (acceptedTypesText.value) {
    parts.push(`Formatos: ${acceptedTypesText.value}`)
  }

  if (props.column.maxSize) {
    parts.push(`Tamanho máximo: ${props.column.maxSize}MB`)
  }

  if (props.column.chunkSize) {
    const chunkSizeMB = Math.round(props.column.chunkSize / (1024 * 1024))
    parts.push(`Upload em chunks de ${chunkSizeMB}MB`)
  }

  return parts.join(' • ')
})

const handleUpdate = (value: string | null) => {
  localErrorMessage.value = ''
  emit('update:modelValue', value)
}

const handleUploadStart = (file: File) => {
  isUploading.value = true
  uploadProgress.value = 0
  localErrorMessage.value = ''
  console.log('Upload started:', file.name)
}

const handleUploadProgress = (progress: number) => {
  uploadProgress.value = progress
}

const handleUploadComplete = (uploadId: string) => {
  isUploading.value = false
  uploadProgress.value = 100
  console.log('Upload completed:', uploadId)

  // Emite o uploadId para o form
  emit('update:modelValue', uploadId)
}

const handleUploadError = (error: string) => {
  isUploading.value = false
  localErrorMessage.value = error
  console.error('Upload error:', error)
}

const handleUploadCancelled = () => {
  isUploading.value = false
  uploadProgress.value = 0
  console.log('Upload cancelled')
}
</script>
