<template>
  <span :title="column.tooltip">{{ value }}</span>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const props = defineProps<{
  record: Record<string, any>
  column: {
    name: string
    format?: string
    tooltip?: string
    [key: string]: any
  }
}>()

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
  
  // TODO: Aplicar formatação de data se necessário
  return result ?? ''
})
</script>
