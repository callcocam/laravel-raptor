<!--
 * FormFieldSection - Section container for organizing form fields with collapsible accordion
 *
 * Modos:
 * - flat (padrão): os campos da seção pertencem ao formData raiz. Ideal para agrupamento visual.
 * - nested (flat: false): os campos ficam em formData[column.name]. Ideal para JSON/relacionamentos.
-->
<template>
  <div class="col-span-12">
    <Collapsible
      v-if="column.collapsible"
      :default-open="column.defaultOpen !== false"
      class="w-full space-y-2"
    >
      <div class="flex items-center justify-between space-x-4">
        <div class="flex-1">
          <h4 v-if="column.label" class="text-sm font-semibold">
            {{ column.label }}
            <span v-if="column.required" class="text-destructive">*</span>
          </h4>
          <p
            v-if="column.helpText || column.hint || column.tooltip"
            class="text-sm text-muted-foreground"
          >
            {{ column.helpText || column.hint || column.tooltip }}
          </p>
        </div>
        <CollapsibleTrigger as-child v-slot="{ toggle }">
          <button type="button" class="h-9 w-9 p-0 inline-flex items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors" @click="toggle">
            <ChevronsUpDown class="h-4 w-4" />
            <span class="sr-only">Toggle</span>
          </button>
        </CollapsibleTrigger>
      </div>

      <CollapsibleContent class="space-y-2">
        <div class="rounded-md border px-4 py-3">
          <div class="grid grid-cols-12 gap-4">
            <div
              v-for="(field, index) in sectionFields"
              :key="field.name"
              :class="getColumnClasses(field)"
            >
              <FieldRenderer
                :column="field"
                :index="index"
                :error="fieldError(field.name)"
                :modelValue="fieldValues[field.name]"
                @update:modelValue="(value) => handleFieldUpdate(field.name, value)"
              />
            </div>
          </div>
        </div>
      </CollapsibleContent>
    </Collapsible>

    <div v-else class="space-y-4">
      <div v-if="column.label || column.helpText || column.hint || column.tooltip">
        <h4 v-if="column.label" class="text-sm font-semibold">
          {{ column.label }}
          <span v-if="column.required" class="text-destructive">*</span>
        </h4>
        <p
          v-if="column.helpText || column.hint || column.tooltip"
          class="text-sm text-muted-foreground"
        >
          {{ column.helpText || column.hint || column.tooltip }}
        </p>
      </div>

      <div class="grid grid-cols-12 gap-4">
        <div
          v-for="(field, index) in sectionFields"
          :key="field.name"
          :class="getColumnClasses(field)"
        >
          <FieldRenderer
            :column="field"
            :index="index"
            :error="fieldError(field.name)"
            :modelValue="fieldValues[field.name]"
            @update:modelValue="(value) => handleFieldUpdate(field.name, value)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, inject, ref, watch, type ComputedRef } from 'vue'
import { ChevronsUpDown } from 'lucide-vue-next'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '~/components/ui/collapsible'
import FieldRenderer from '~/components/form/FieldRenderer.vue'
import { useGridLayout } from '~/composables/useGridLayout'
import { createMultiFieldUpdate, isMultiFieldUpdate } from '~/types/form'
import type { FieldEmitValue } from '~/types/form'

interface SectionField {
  name: string
  label: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  helpText?: string
  columnSpan?: string
  [key: string]: any
}

interface FormColumn {
  name: string
  label?: string
  required?: boolean
  tooltip?: string
  helpText?: string
  hint?: string
  fields?: SectionField[]
  collapsible?: boolean
  defaultOpen?: boolean
  /**
   * flat: true (padrão) → campos pertencem ao formData raiz (agrupamento visual).
   * flat: false → campos ficam em formData[name] (relacionamento/JSON).
   */
  flat?: boolean
}

interface Props {
  column: FormColumn
  modelValue?: Record<string, any> | string | null
  error?: Record<string, string | string[]>
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => ({}),
  error: () => ({}),
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: FieldEmitValue): void
}>()

const { getColumnClasses } = useGridLayout()

// Injeta o formData raiz provido pelo FormRenderer (para modo flat)
const rootFormData = inject<ComputedRef<Record<string, any>>>('formData')

// Modo flat: campos vivem no formData raiz, não aninhados sob column.name
const isFlat = computed(() => props.column.flat !== false)

const fieldValues = ref<Record<string, any>>({})

const sectionFields = computed(() => props.column.fields || [])

// Fonte de dados para leitura: raiz (flat) ou modelValue (nested)
const sourceData = computed((): Record<string, any> => {
  if (isFlat.value) {
    return rootFormData?.value ?? {}
  }
  const v = props.modelValue
  return typeof v === 'object' && v !== null ? v : {}
})

// Inicializa/sincroniza os valores dos campos conforme a fonte
watch(
  sourceData,
  (data) => {
    sectionFields.value.forEach((field) => {
      const key = field.name
      if (key.includes('.')) {
        fieldValues.value[key] = getNestedValue(data, key) ?? ''
      } else {
        fieldValues.value[key] = data[key] ?? ''
      }
    })
  },
  { immediate: true, deep: true },
)

function getNestedValue(obj: Record<string, any>, path: string): any {
  return path.split('.').reduce((curr, key) => {
    if (curr && typeof curr === 'object' && !Array.isArray(curr) && key in curr) {
      return curr[key]
    }
    return undefined
  }, obj as any)
}

function fieldError(fieldName: string): string | string[] | undefined {
  return props.error?.[fieldName]
}

function handleFieldUpdate(fieldName: string, value: FieldEmitValue) {
  if (isMultiFieldUpdate(value)) {
    Object.entries(value.fields).forEach(([key, val]) => {
      fieldValues.value[key] = val
    })
  } else {
    fieldValues.value[fieldName] = value
  }

  if (isFlat.value) {
    // Modo flat: propaga cada campo diretamente para o formData raiz
    const updated = isMultiFieldUpdate(value)
      ? { ...value.fields }
      : { [fieldName]: value }
    emit('update:modelValue', createMultiFieldUpdate(updated))
  } else {
    // Modo nested: propaga como objeto aninhado sob column.name
    emit('update:modelValue', createMultiFieldUpdate({ [props.column.name]: { ...fieldValues.value } }))
  }
}
</script>
