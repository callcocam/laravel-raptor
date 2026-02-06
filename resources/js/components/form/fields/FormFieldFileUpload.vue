<!--
 * FormFieldFileUpload - File upload field with local preview
 *
 * Simple upload with instant preview before form submission
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <!-- Preview de imagem -->
    <div v-if="previewUrl && isImageFile" class="mb-4">
      <div class="relative w-full max-w-md mx-auto rounded-lg overflow-hidden border-2 border-border">
        <img :src="previewUrl" :alt="selectedFiles[0]?.name || 'Preview'" class="w-full h-auto" />
        <div class="absolute top-2 right-2">
          <Button
            type="button"
            variant="destructive"
            size="icon"
            class="h-8 w-8"
            @click="removeFile(0)"
          >
            <X class="h-4 w-4" />
          </Button>
        </div>
      </div>
      <p v-if="selectedFiles[0]" class="text-center text-sm text-muted-foreground mt-2">
        {{ selectedFiles[0].name }} ({{ formatFileSize(selectedFiles[0].size || 0) }})
      </p>
    </div>

    <!-- Upload area (oculto se já tem arquivo) -->
    <div
      v-show="selectedFiles.length === 0"
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

    <!-- Lista de arquivos não-imagem -->
    <div v-if="selectedFiles.length > 0 && !isImageFile" class="space-y-2">
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
import { ref, computed, watch, onMounted } from 'vue'
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
  modelValue?: File | File[] | string | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

console.log('Props:', props.modelValue)

const emit = defineEmits<{
  (e: 'update:modelValue', value: File | File[] | null): void
}>()

const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])
const isDragging = ref(false)
const localErrorMessage = ref('')
const previewUrl = ref<string | null>(null)

// Inicializa preview com URL existente
onMounted(() => {
  if (props.modelValue && typeof props.modelValue === 'string') {
    previewUrl.value = props.modelValue
  }
})

// Observa mudanças no modelValue para atualizar preview
watch(() => props.modelValue, (newValue) => {
  if (newValue && typeof newValue === 'string') {
    previewUrl.value = newValue
  } else if (!newValue) {
    previewUrl.value = null
  }
})

const hasError = computed(() => !!props.error || !!localErrorMessage.value)

const isImageFile = computed(() => {
  // Se há uma URL de preview (string), é uma imagem
  if (previewUrl.value && typeof props.modelValue === 'string') {
    return true
  }
  
  // Se há arquivos selecionados, verifica o tipo
  if (selectedFiles.value.length === 0) return false
  return selectedFiles.value[0].type.startsWith('image/')
})

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
    const fileMimeType = file.type

    const isValidType = props.column.acceptedFileTypes.some(type => {
      const acceptedType = type.toLowerCase()

      // Verifica se é um wildcard (ex: image/*, video/*)
      if (acceptedType.includes('/*')) {
        const [category] = acceptedType.split('/')
        return fileMimeType.startsWith(category + '/')
      }

      // Verifica se é uma extensão (ex: .jpg, .png)
      if (acceptedType.startsWith('.')) {
        return acceptedType === fileExtension
      }

      // Verifica se é um MIME type completo (ex: image/jpeg)
      return acceptedType === fileMimeType
    })

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
    // Limpa preview anterior
    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value)
      previewUrl.value = null
    }
  }

  for (const file of filesArray) {
    if (validateFile(file)) {
      selectedFiles.value.push(file)

      // Gera preview se for imagem
      if (file.type.startsWith('image/')) {
        generatePreview(file)
      }
    } else {
      break
    }
  }

  emitValue()
}

const generatePreview = (file: File) => {
  // Limpa preview anterior
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }

  // Gera novo preview usando FileReader
  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

const removeFile = (index: number) => {
  // Se há arquivos selecionados, remove do array
  if (selectedFiles.value.length > 0) {
    selectedFiles.value.splice(index, 1)
  }
  
  localErrorMessage.value = ''

  // Limpa preview
  if (previewUrl.value) {
    // Só revoga se foi criado com createObjectURL (não é uma URL http)
    if (!previewUrl.value.startsWith('http')) {
      URL.revokeObjectURL(previewUrl.value)
    }
    previewUrl.value = null
  }

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
