<script setup lang="ts">
import Icon from '~/components/icon.vue'

 

interface Column {
  name: string
  label: string
  value?: string | number | null
  prefix?: string | null
  suffix?: string | null
  icon?: string | null
  tooltip?: string | null
  default?: string | null
}

interface Props {
  column: Column
}

const props = defineProps<Props>()

const displayValue = props.column.value ?? props.column.default ?? '-'
</script>

<template>
  <div class="flex items-center gap-2">
    <Icon v-if="column.icon" :is="column.icon" class="h-4 w-4 text-muted-foreground" />
    <span class="text-sm">
      <span v-if="column.prefix" class="text-muted-foreground">{{ column.prefix }} </span>
      {{ displayValue }}
      <span v-if="column.suffix" class="text-muted-foreground"> {{ column.suffix }}</span>
    </span>
  </div>
</template>
