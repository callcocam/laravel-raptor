<template>
  <AdvancedTable v-bind="$props" />
</template>

<script setup lang="ts">
import AdvancedTable from './AdvancedTable.vue'

withDefaults(
  defineProps<{
    tableKey?: string
  }>(),
  {
    tableKey: 'table',
  },
)
</script>
