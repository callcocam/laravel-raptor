<!--
 * FilterNullable - Filtro para valores null, vazios, zero e especiais
 * 
 * Permite filtrar por:
 * - Valores null/not null
 * - Valores vazios/não vazios
 * - Zero/maior que zero
 * - Combinações customizadas
 -->
<template>
  <Select 
    class="h-9"
    :modelValue="modelValue ? String(modelValue) : 'all'"
    @update:modelValue="handleSelect"
  >
    <SelectTrigger :id="filter.name" class="w-full">
      <SelectValue :placeholder="filter.placeholder || filter.label" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem
        v-for="option in filter.options"
        :key="option.value"
        :value="String(option.value)"
      >
        {{ option.label }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>

<script setup lang="ts">
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

interface FilterOption {
  value: string | number
  label: string
}

interface Props {
  filter: {
    name: string
    label: string
    placeholder?: string
    options: FilterOption[]
    [key: string]: any
  }
  modelValue?: string | number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const handleSelect = (value: string) => {
  // Se for 'all', limpa o filtro
  if (value === 'all') {
    emit('update:modelValue', null)
    return
  }

  // Se o valor original era número, converte de volta
  const originalValue = props.filter.options?.find(opt => String(opt.value) === value)?.value
  emit('update:modelValue', originalValue || value)
}
</script>
