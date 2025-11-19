<!--
 * FilterDateRange - Filtro de intervalo de datas
 * 
 * Permite selecionar uma data inicial e final
 * Retorna um objeto { from: string, to: string }
 -->
<template>
  <div class="flex items-center gap-2">
    <Input
      :modelValue="internalValue.from"
      @update:modelValue="handleFromChange"
      :placeholder="`${filter.label} (de)`"
      type="date"
      class="w-auto min-w-[150px]"
    />
    <span class="text-muted-foreground">até</span>
    <Input
      :modelValue="internalValue.to"
      @update:modelValue="handleToChange"
      :placeholder="`${filter.label} (até)`"
      type="date"
      class="w-auto min-w-[150px]"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Input } from '@/components/ui/input'

interface DateRange {
  from?: string
  to?: string
}

interface Props {
  filter: {
    name: string
    label: string
    [key: string]: any
  }
  modelValue?: DateRange
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: DateRange): void
}>()

const internalValue = computed<DateRange>(() => {
  return props.modelValue || { from: '', to: '' }
})

const handleFromChange = (value: string) => {
  emit('update:modelValue', {
    ...internalValue.value,
    from: value
  })
}

const handleToChange = (value: string) => {
  emit('update:modelValue', {
    ...internalValue.value,
    to: value
  })
}
</script>
