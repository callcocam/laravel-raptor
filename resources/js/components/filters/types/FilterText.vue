<!--
 * FilterText - Filtro de texto simples
 * 
 * Permite busca por texto em campos específicos
 * Atualiza modelValue conforme o usuário digita
 -->
<template>
  <Input
    :id="filter.name"
    :modelValue="modelValue"
    @update:modelValue="handleInput"
    :placeholder="filter.placeholder || filter.label"
    type="text"
    class="w-auto min-w-[150px]"
  />
</template>

<script setup lang="ts">
import { Input } from '@/components/ui/input'

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

const handleInput = (value: string) => {
  emit('update:modelValue', value)
}
</script>
