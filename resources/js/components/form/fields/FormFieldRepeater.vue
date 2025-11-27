<!--
 * FormFieldRepeater - Repeatable field group component
 *
 * Allows adding/removing multiple instances of a field group
 * Supports min/max items, collapsible items, and reordering
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <FieldDescription v-if="column.helpText || column.hint">
      {{ column.helpText || column.hint }}
    </FieldDescription>

    <!-- Items List -->
    <div class="space-y-3">
      <!-- Draggable Container (only when orderable) -->
      <Draggable
        v-if="column.orderable && items.length > 0"
        v-model="items"
        item-key="_id"
        handle=".drag-handle"
        :animation="200"
        ghost-class="opacity-50"
        drag-class="cursor-grabbing"
        class="space-y-3"
        @end="emitValue"
      >
        <template #item="{ element: item, index }">
          <RepeaterItem
            :item="item"
            :itemId="item._id"
            :index="index"
            :isLast="index === items.length - 1"
            :fields="fields"
            :errors="itemErrors"
            :collapsible="column.collapsible"
            :orderable="column.orderable"
            :canRemove="canRemoveItem"
            :canDuplicate="column.allowDuplication"
            @updateField="(fieldName, value) => updateItemField(index, fieldName, value)"
            @remove="removeItem"
            @duplicate="duplicateItem"
            @moveUp="moveItemUp"
            @moveDown="moveItemDown"
          />
        </template>
      </Draggable>

      <!-- Non-draggable list (when not orderable) -->
      <template v-else-if="items.length > 0">
        <RepeaterItem
          v-for="(item, index) in items"
          :key="item._id"
          :item="item"
          :itemId="item._id"
          :index="index"
          :isLast="index === items.length - 1"
          :fields="fields"
          :errors="itemErrors"
          :collapsible="column.collapsible"
          :orderable="column.orderable"
          :canRemove="canRemoveItem"
          :canDuplicate="column.allowDuplication"
          @updateField="(fieldName, value) => updateItemField(index, fieldName, value)"
          @remove="removeItem"
          @duplicate="duplicateItem"
          @moveUp="moveItemUp"
          @moveDown="moveItemDown"
        />
      </template>

      <!-- Empty State -->
      <RepeaterEmptyState
        v-if="items.length === 0"
        :emptyTitle="column.emptyTitle"
        :emptyDescription="column.emptyDescription"
        :addButtonLabel="column.addButtonLabel || 'Adicionar item'"
        :showAddButton="canAddItem"
        @add="addItem"
      />
    </div>

    <!-- Actions -->
    <RepeaterActions
      :totalItems="items.length"
      :canAdd="canAddItem"
      :canClearAll="canClearAll"
      :collapsible="column.collapsible"
      :addButtonLabel="column.addButtonLabel || 'Adicionar item'"
      @add="addItem"
      @clearAll="clearAll"
      @collapseAll="collapseAll"
      @expandAll="expandAll"
    />

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed, ref, watch, inject } from 'vue'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import Draggable from 'vuedraggable'
import RepeaterItem from './repeater/RepeaterItem.vue'
import RepeaterActions from './repeater/RepeaterActions.vue'
import RepeaterEmptyState from './repeater/RepeaterEmptyState.vue'
import { useRepeaterCalculations, type Calculation } from '~/composables/useRepeaterCalculations'

interface FormColumn {
  name: string
  label?: string
  type?: string
  component?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  helpText?: string
  hint?: string
  tooltip?: string
  default?: any
  columnSpan?: string
  [key: string]: any
}

interface RepeaterColumn {
  name: string
  label?: string
  helpText?: string
  hint?: string
  required?: boolean
  minItems?: number
  maxItems?: number
  addButtonLabel?: string
  removeButtonLabel?: string
  defaultItems?: any[]
  collapsible?: boolean
  orderable?: boolean
  allowDuplication?: boolean
  emptyTitle?: string
  emptyDescription?: string
  fields?: FormColumn[]
  calculations?: Calculation[]
}

interface Props {
  column: RepeaterColumn
  modelValue?: any[]
  error?: string | string[] | Record<string, any>
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: any[]): void
}>()

// Injeta formData do formulário principal para atualizar campos calculados
const parentFormData = inject<any>('formData', ref({}))

// Ensure errors passed to RepeaterItem are always Record<string, any> | undefined
const itemErrors = computed<Record<string, any> | undefined>(() => {
  if (!props.error) return undefined
  if (typeof props.error === 'object' && !Array.isArray(props.error)) {
    return props.error
  }
  return undefined
})

// Internal items with unique IDs
const items = ref<Array<any & { _id: string }>>(
  (props.modelValue || []).map((item, index) => ({
    ...item,
    _id: `item-${Date.now()}-${index}`,
  }))
)

// Initialize with default items if empty
if (items.value.length === 0 && props.column.defaultItems && props.column.defaultItems.length > 0) {
  items.value = props.column.defaultItems.map((item, index) => ({
    ...item,
    _id: `item-${Date.now()}-${index}`,
  }))
}

// Initialize with minItems if needed
if (items.value.length === 0 && props.column.minItems && props.column.minItems > 0) {
  items.value = Array.from({ length: props.column.minItems }, (_, index) => ({
    _id: `item-${Date.now()}-${index}`,
  }))
}

// Setup calculations
const calculations = computed(() => props.column.calculations || [])
const { calculationResults, getCalculatedValue, isCalculatedField } = useRepeaterCalculations(
  items,
  calculations.value
)

// Watch for external changes to modelValue
watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue || newValue.length === 0) {
      items.value = []
      return
    }
    
    // Only update if the values actually changed
    const currentData = items.value.map(({ _id, ...rest }) => rest)
    if (JSON.stringify(currentData) !== JSON.stringify(newValue)) {
      items.value = newValue.map((item, index) => ({
        ...item,
        _id: item._id || `item-${Date.now()}-${index}`,
      }))
    }
  }
)

// Computed
const fields = computed(() => props.column.fields || [])

const hasError = computed(() => !!props.error)

const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  if (typeof props.error === 'string') {
    return [{ message: props.error }]
  }
  return []
})

const canAddItem = computed(() => {
  if (props.column.maxItems) {
    return items.value.length < props.column.maxItems
  }
  return true
})

const canRemoveItem = computed(() => {
  if (props.column.minItems) {
    return items.value.length > props.column.minItems
  }
  return true
})

const canClearAll = computed(() => {
  return items.value.length > 0 && !props.column.minItems
})

// Methods
function addItem(): void {
  if (!canAddItem.value) return
  
  const newItem: any = {
    _id: `item-${Date.now()}-${items.value.length}`,
  }
  
  // Initialize with default values from fields
  fields.value.forEach(field => {
    if (field.default !== undefined) {
      newItem[field.name] = field.default
    }
  })
  
  items.value.push(newItem)
  emitValue()
}

function removeItem(index: number): void {
  if (!canRemoveItem.value) return
  
  items.value.splice(index, 1)
  emitValue()
}

function duplicateItem(index: number): void {
  const itemToDuplicate = items.value[index]
  
  const newItem: any = {
    ...JSON.parse(JSON.stringify(itemToDuplicate)),
    _id: `item-${Date.now()}-${items.value.length}`,
  }

  items.value.splice(index + 1, 0, newItem)
  emitValue()
}

function moveItemUp(index: number): void {
  if (index === 0) return

  const temp = items.value[index]
  items.value[index] = items.value[index - 1]
  items.value[index - 1] = temp

  emitValue()
}

function moveItemDown(index: number): void {
  if (index === items.value.length - 1) return

  const temp = items.value[index]
  items.value[index] = items.value[index + 1]
  items.value[index + 1] = temp

  emitValue()
}

function updateItemField(index: number, fieldName: string, value: any): void {
  if (items.value[index]) {
    items.value[index][fieldName] = value
    
    // Atualiza campos calculados se houver
    updateCalculatedFields()
    
    emitValue()
  }
}

/**
 * Atualiza campos que são resultados de cálculos
 */
function updateCalculatedFields(): void {
  if (!calculations.value || calculations.value.length === 0) {
    return
  }

  // Para cada item, atualiza os campos calculados dentro do item
  items.value.forEach((item) => {
    Object.keys(calculationResults.value).forEach(fieldName => {
      // Só atualiza se o campo existir nas definições do repeater
      const fieldExists = fields.value.some(f => f.name === fieldName)
      if (fieldExists) {
        const calculatedValue = getCalculatedValue(fieldName)
        if (calculatedValue !== null) {
          item[fieldName] = calculatedValue
        }
      }
    })
  })

  // Atualiza campos calculados no formData principal (fora do repeater)
  if (parentFormData.value && typeof parentFormData.value === 'object') {
    Object.keys(calculationResults.value).forEach(fieldName => {
      // Se o campo NÃO existe dentro do repeater, atualiza no formData principal
      const fieldExistsInRepeater = fields.value.some(f => f.name === fieldName)
      if (!fieldExistsInRepeater) {
        const calculatedValue = getCalculatedValue(fieldName)
        if (calculatedValue !== null) {
          parentFormData.value[fieldName] = calculatedValue
        }
      }
    })
  }
}

function clearAll(): void {
  if (!canClearAll.value) return

  if (confirm('Tem certeza que deseja remover todos os itens?')) {
    items.value = []
    emitValue()
  }
}

function collapseAll(): void {
  // This will be handled by RepeaterItem internally via event
  // We emit an event that RepeaterItem can listen to
}

function expandAll(): void {
  // This will be handled by RepeaterItem internally via event
  // We emit an event that RepeaterItem can listen to
}

function emitValue(): void {
  // Remove _id before emitting
  const cleanItems = items.value.map(({ _id, ...rest }) => rest)
  emit('update:modelValue', cleanItems)
}

// Watch para recalcular quando items mudar
watch(
  () => items.value,
  () => {
    if (calculations.value && calculations.value.length > 0) {
      updateCalculatedFields()
    }
  },
  { deep: true }
)

// Expor valores calculados para uso externo (opcional)
defineExpose({
  calculationResults,
  getCalculatedValue,
  isCalculatedField,
})
</script>
