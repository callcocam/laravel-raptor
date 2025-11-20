<script lang="ts" setup>
import Icon from '~/components/icon.vue'

interface Column {
  text?: string
  value?: string
  icon?: string
  tooltip?: string
}

interface Props {
  column: Column
}

const props = defineProps<Props>()

const displayValue = props.column.text || props.column.value || '-'
</script>

<template>
  <div class="inline-flex items-center gap-2" :title="column.tooltip">
    <Icon v-if="column.icon" :is="column.icon" class="h-4 w-4 text-muted-foreground" />
    <time class="text-sm">{{ displayValue }}</time>
  </div>
</template>
