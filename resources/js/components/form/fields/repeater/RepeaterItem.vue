<!--
 * RepeaterItem - Individual item in a repeater field
 *
 * Handles rendering fields, collapse/expand, and removal
 -->
<template>
  <div
    class="relative border rounded-lg bg-card transition-all duration-200 hover:shadow-md"
    :class="{
      'border-primary/50': isDragging,
      'opacity-50': isDragging,
    }"
  >
    <!-- Item Header -->
    <div
      class="flex items-center justify-between px-4 py-3 border-b bg-muted/30"
      :class="{ 'cursor-pointer': collapsible }"
      @click="collapsible ? toggleCollapse() : null"
    >
      <div class="flex items-center gap-3">
        <!-- Drag Handle -->
        <button
          v-if="orderable"
          type="button"
          class="drag-handle cursor-grab active:cursor-grabbing text-muted-foreground hover:text-foreground transition-colors"
          title="Arrastar para reordenar"
        >
          <GripVertical class="h-5 w-5" />
        </button>

        <!-- Collapse Icon -->
        <component
          v-if="collapsible"
          :is="isCollapsed ? ChevronRight : ChevronDown"
          class="h-4 w-4 text-muted-foreground transition-transform"
        />

        <!-- Item Number/Label -->
        <div class="flex items-center gap-2">
          <span class="font-medium text-sm">
            Item {{ index + 1 }}
          </span>
          <span v-if="hasErrors" class="text-destructive text-xs">
            (com erros)
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <!-- Move Up -->
        <Button
          v-if="orderable && index > 0"
          type="button"
          variant="ghost"
          size="icon"
          class="h-7 w-7"
          @click.stop="$emit('moveUp', index)"
        >
          <ChevronUp class="h-4 w-4" />
        </Button>

        <!-- Move Down -->
        <Button
          v-if="orderable && !isLast"
          type="button"
          variant="ghost"
          size="icon"
          class="h-7 w-7"
          @click.stop="$emit('moveDown', index)"
        >
          <ChevronDown class="h-4 w-4" />
        </Button>

        <!-- Duplicate -->
        <Button
          v-if="canDuplicate"
          type="button"
          variant="ghost"
          size="icon"
          class="h-7 w-7"
          @click.stop="$emit('duplicate', index)"
        >
          <Copy class="h-4 w-4" />
        </Button>

        <!-- Remove -->
        <Button
          v-if="canRemove"
          type="button"
          variant="ghost"
          size="icon"
          class="h-7 w-7 text-destructive hover:text-destructive hover:bg-destructive/10"
          @click.stop="$emit('remove', index)"
        >
          <Trash2 class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- Item Fields -->
    <div
      v-show="!isCollapsed"
      class="p-4 grid grid-cols-12 gap-4"
    >
      <component
        v-for="field in fields"
        :key="`${itemId}-${field.name}`"
        :is="getFieldComponent(field)"
        :column="field"
        :modelValue="item[field.name]"
        :error="getFieldError(field.name)"
        @update:modelValue="(value: any) => $emit('updateField', field.name, value)"
        :class="getFieldColumnClass(field)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
  ChevronDown,
  ChevronRight,
  ChevronUp,
  GripVertical,
  Copy,
  Trash2,
} from 'lucide-vue-next'

// Import field components
import FormFieldText from '../FormFieldText.vue'
import FormFieldEmail from '../FormFieldEmail.vue'
import FormFieldPassword from '../FormFieldPassword.vue'
import FormFieldTextarea from '../FormFieldTextarea.vue'
import FormFieldNumber from '../FormFieldNumber.vue'
import FormFieldDate from '../FormFieldDate.vue'
import FormFieldCheckbox from '../FormFieldCheckbox.vue'
import FormFieldSelect from '../FormFieldSelect.vue'
import FormFieldCombobox from '../FormFieldCombobox.vue'
import FormFieldFileUpload from '../FormFieldFileUpload.vue'

interface FormColumn {
  name: string
  label?: string
  component?: string
  columnSpan?: string
  [key: string]: any
}

interface Props {
  item: any
  itemId: string
  index: number
  isLast: boolean
  fields: FormColumn[]
  errors?: Record<string, any>
  collapsible?: boolean
  orderable?: boolean
  canRemove?: boolean
  canDuplicate?: boolean
  isDragging?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  errors: () => ({}),
  collapsible: false,
  orderable: false,
  canRemove: true,
  canDuplicate: false,
  isDragging: false,
})

defineEmits<{
  (e: 'updateField', fieldName: string, value: any): void
  (e: 'remove', index: number): void
  (e: 'duplicate', index: number): void
  (e: 'moveUp', index: number): void
  (e: 'moveDown', index: number): void
}>()

// Component registry
const componentRegistry: Record<string, any> = {
  'form-field-text': FormFieldText,
  'form-field-email': FormFieldEmail,
  'form-field-password': FormFieldPassword,
  'form-field-textarea': FormFieldTextarea,
  'form-field-number': FormFieldNumber,
  'form-field-date': FormFieldDate,
  'form-field-checkbox': FormFieldCheckbox,
  'form-field-select': FormFieldSelect,
  'form-field-combobox': FormFieldCombobox,
  'form-field-file-upload': FormFieldFileUpload,
}

const isCollapsed = ref(false)

const hasErrors = computed(() => {
  if (!props.errors) return false
  return Object.keys(props.errors).some(key => key.startsWith(`${props.index}.`))
})

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

const getFieldComponent = (field: FormColumn) => {
  const componentName = field.component || 'form-field-text'
  return componentRegistry[componentName] || FormFieldText
}

const getFieldColumnClass = (field: FormColumn) => {
  const span = field.columnSpan || 'full'
  
  if (span === 'full') {
    return 'col-span-12'
  }
  
  return `col-span-12 md:col-span-${span}`
}

const getFieldError = (fieldName: string) => {
  if (!props.errors) return undefined
  
  const errorKey = `${props.index}.${fieldName}`
  if (errorKey in props.errors) {
    return props.errors[errorKey]
  }
  
  return undefined
}
</script>
