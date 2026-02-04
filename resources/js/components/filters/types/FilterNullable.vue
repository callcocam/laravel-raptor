<!--
 * FilterNullable - Toggle filter para valores null/not null
 * 
 * Comportamento:
 * - false: Filtra apenas registros NULL (whereNull)
 * - true: Filtra apenas registros NOT NULL (whereNotNull)
 * - null/undefined: Não aplica filtro (todos registros)
 -->
<template>
  <div class="flex items-center gap-3">
    <Checkbox :id="filter.name"
      :modelValue="modelValue === true ? true : modelValue === false ? false : 'indeterminate'"
      @update:modelValue="handleToggle" />
    <Label :for="filter.name" class="text-sm font-medium cursor-pointer select-none">
      {{ getCurrentLabel() }}
    </Label>
  </div>
</template>

<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'

interface Props {
  filter: {
    name: string
    label: string
    trueLabel?: string
    falseLabel?: string
    [key: string]: any
  }
  modelValue?: boolean | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean | null): void
}>()

const handleToggle = (checked: boolean | 'indeterminate') => {
  if (checked === 'indeterminate') {
    // Clica novamente no indeterminate = false
    emit('update:modelValue', false)
  } else if (checked === false) {
    // false = whereNull (apenas registros nulos)
    emit('update:modelValue', false)
  } else {
    // true = whereNotNull (apenas registros não nulos)
    emit('update:modelValue', true)
  }
}

const getCurrentLabel = () => {
  const trueLabel = props.filter.trueLabel || 'Not Null'
  const falseLabel = props.filter.falseLabel || 'Null'

  if (props.modelValue === true) return trueLabel
  if (props.modelValue === false) return falseLabel
  return props.filter.label
}
</script>