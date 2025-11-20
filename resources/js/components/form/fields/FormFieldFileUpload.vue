<!--
 * FormFieldFileUpload - File upload field using shadcn-vue Field primitives
 *
 * Modern replacement for FormColumnFileUpload with improved accessibility
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <div
      class="relative border-2 border-dashed rounded-lg p-6 transition-colors"
      :class="{
        'border-primary bg-primary/5': isDragging,
        'border-border hover:border-primary/50': !isDragging && !hasError,
        'border-destructive': hasError,
      }"
      @dragover.prevent="handleDragOver"
      @dragleave.prevent="handleDragLeave"
      @drop.prevent="handleDrop"
    >
      <input
        :id="column.name"
        ref="fileInputRef"
        type="file"
        :name="column.name"
        :accept="acceptAttribute"
        :multiple="column.multiple"
        :required="column.required"
        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
        @change="handleFileChange"
        :aria-invalid="hasError"
      />

      <div class="text-center">
        <Upload class="mx-auto h-12 w-12 text-muted-foreground mb-2" />

        <div class="text-sm">
          <span class="font-medium text-primary">Clique para selecionar</span>
          <span class="text-muted-foreground"> ou arraste o arquivo aqui</span>
        </div>

        <p v-if="acceptedTypesText" class="text-xs text-muted-foreground mt-2">
          Formatos aceitos: {{ acceptedTypesText }}
        </p>

        <p v-if="column.maxSize" class="text-xs text-muted-foreground">
          Tamanho máximo: {{ column.maxSize }}MB
        </p>
      </div>
    </div>

    <div v-if="selectedFiles.length > 0" class="space-y-2">
      <div
        v-for="(file, index) in selectedFiles"
        :key="index"
        class="flex items-center justify-between p-3 bg-muted rounded-lg"
      >
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <FileIcon class="h-5 w-5 text-muted-foreground flex-shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ file.name }}</p>
            <p class="text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</p>
          </div>
        </div>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="h-8 w-8 flex-shrink-0"
          @click="removeFile(index)"
        >
          <X class="h-4 w-4" />
        </Button>
      </div>
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
import { Button } from '@/components/ui/button'
import { Upload, FileIcon, X } from 'lucide-vue-next'

interface FormColumn {
  name: string
  label?: string
  required?: boolean
  acceptedFileTypes?: string[]
  maxSize?: number
  multiple?: boolean
  tooltip?: string
  helpText?: string
  hint?: string
}

interface Props {
  column: FormColumn
  modelValue?: File | File[] | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: File | File[] | null): void
}>()

const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])
const isDragging = ref(false)
const localErrorMessage = ref('')

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
    return undefined
  }
  return props.column.acceptedFileTypes.join(',')
})

const acceptedTypesText = computed(() => {
  if (!props.column.acceptedFileTypes || props.column.acceptedFileTypes.length === 0) {
    return null
  }
  return props.column.acceptedFileTypes.map(type => type.replace('.', '').toUpperCase()).join(', ')
})

const validateFile = (file: File): boolean => {
  localErrorMessage.value = ''

  if (props.column.acceptedFileTypes && props.column.acceptedFileTypes.length > 0) {
    const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase()
    const isValidType = props.column.acceptedFileTypes.some(
      type => type.toLowerCase() === fileExtension
    )

    if (!isValidType) {
      localErrorMessage.value = `Tipo de arquivo não permitido. Aceitos: ${acceptedTypesText.value}`
      return false
    }
  }

  if (props.column.maxSize) {
    const maxSizeInBytes = props.column.maxSize * 1024 * 1024
    if (file.size > maxSizeInBytes) {
      localErrorMessage.value = `Arquivo muito grande. Máximo: ${props.column.maxSize}MB`
      return false
    }
  }

  return true
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'

  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const addFiles = (files: FileList | File[]) => {
  const filesArray = Array.from(files)

  if (!props.column.multiple && filesArray.length > 1) {
    localErrorMessage.value = 'Apenas um arquivo é permitido'
    return
  }

  if (!props.column.multiple) {
    selectedFiles.value = []
  }

  for (const file of filesArray) {
    if (validateFile(file)) {
      selectedFiles.value.push(file)
    } else {
      break
    }
  }

  emitValue()
}

const removeFile = (index: number) => {
  selectedFiles.value.splice(index, 1)
  localErrorMessage.value = ''
  emitValue()

  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const emitValue = () => {
  if (selectedFiles.value.length === 0) {
    emit('update:modelValue', null)
  } else if (props.column.multiple) {
    emit('update:modelValue', selectedFiles.value)
  } else {
    emit('update:modelValue', selectedFiles.value[0])
  }
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    addFiles(target.files)
  }
}

const handleDragOver = () => {
  isDragging.value = true
}

const handleDragLeave = () => {
  isDragging.value = false
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  if (event.dataTransfer?.files) {
    addFiles(event.dataTransfer.files)
  }
}
</script>
