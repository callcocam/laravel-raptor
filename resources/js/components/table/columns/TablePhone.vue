<template>
  <a
    v-if="value"
    :href="`tel:${value}`"
    class="text-primary hover:underline"
    :title="column.tooltip"
  >
    {{ value }}
  </a>
  <span v-else class="text-muted-foreground">—</span>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const props = defineProps<{
  record: Record<string, any>
  column: {
    name: string
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
  
  return result ?? ''
})
</script>
