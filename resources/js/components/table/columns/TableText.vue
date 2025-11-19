<template>
  <span>{{ value }}</span>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const props = defineProps<{
  record: Record<string, any>
  column: {
    name: string
    [key: string]: any
  }
}>()

/**
 * Obtém o valor da propriedade, suportando acesso aninhado (ex: 'category.name')
 */
const value = computed(() => {
  const keys = props.column.name.split('.')
  let result = props.record
  
  for (const key of keys) {
    if (result && typeof result === 'object' && key in result) {
      result = result[key]
    } else {
      return ''
    }
  }
  
  return result ?? ''
})
</script>
