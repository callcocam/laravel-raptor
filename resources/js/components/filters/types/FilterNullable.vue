<!--
 * FilterNullable - Toggle filter para valores null/not null
 * 
 * Comportamento:
 * - false: Filtra apenas registros NULL (whereNull)
 * - true: Filtra apenas registros NOT NULL (whereNotNull)
 * - null/undefined: Não aplica filtro (todos registros)
 -->
<template>
  <div class="flex justify-center flex-col gap-y-2">
    <div class="flex items-center gap-3">
      <Checkbox :id="filter.name" v-model:model-value="checkboxState" @update:modelValue="handleToggle" />
      <Label :for="filter.name" class="text-sm font-medium cursor-pointer select-none">
        {{ getCurrentLabel() }}
      </Label>
    </div>
    <FieldDescription v-if="filter.placeholder">
      {{ filter.placeholder }}
    </FieldDescription>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import { FieldDescription } from '@/components/ui/field'

interface Props {
  filter: {
    name: string
    label: string
    trueLabel?: string
    falseLabel?: string
    [key: string]: any
  }
  modelValue?: boolean | null | string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean | null): void
}>()

// Normalize the modelValue to handle string inputs from URL query
const normalizedModelValue = computed(() => {
  if (props.modelValue === 'true') return true
  if (props.modelValue === 'false') return false
  if (props.modelValue === null || props.modelValue === undefined) return null
  return props.modelValue as boolean
})

const checkboxState = computed(() => {
  if (normalizedModelValue.value === true) return true
  if (normalizedModelValue.value === false) return false
  return 'indeterminate'
})

const handleToggle = () => {
  const currentValue = normalizedModelValue.value

  if (currentValue === null) {
    // From indeterminate (all) to true (not null)
    emit('update:modelValue', true)
  } else if (currentValue === true) {
    // From true (not null) to false (null)
    emit('update:modelValue', false)
  } else {
    // From false (null) to indeterminate (all)
    emit('update:modelValue', null)
  }
}

const getCurrentLabel = () => {
  const trueLabel = props.filter.trueLabel || 'Not Null'
  const falseLabel = props.filter.falseLabel || 'Null'

  if (normalizedModelValue.value === true) return trueLabel
  if (normalizedModelValue.value === false) return falseLabel
  return props.filter.label
}
</script>