<!--
 * FilterTrashed - Filtro para registros deletados
 * 
 * Permite filtrar por registros ativos, deletados ou todos
 * Específico para soft deletes do Laravel
 -->
<template>
  <Select
    :modelValue="modelValue ? String(modelValue) : undefined"
    @update:modelValue="handleSelect"
  >
    <SelectTrigger :id="filter.name" class="w-auto min-w-[150px]">
      <SelectValue :placeholder="filter.placeholder || filter.label" />
    </SelectTrigger>
    <SelectContent> 
      <SelectItem value="only">Apenas deletados</SelectItem>
      <SelectItem value="with">Incluir deletados</SelectItem>
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

interface Props {
  filter: {
    name: string
    label: string
    placeholder?: string
    [key: string]: any
  }
  modelValue?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const handleSelect = (value: string) => {
  emit('update:modelValue', value)
}
</script>
