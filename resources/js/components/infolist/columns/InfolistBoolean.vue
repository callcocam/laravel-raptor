<template>
  <div class="inline-flex items-center gap-2" :title="column.tooltip">
    <span
      :class="[
        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
        booleanClasses
      ]"
    >
      <Icon v-if="column.icon" :is="column.icon" class="h-3 w-3" />
      {{ column.text }}
    </span>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import Icon from '~/components/icon.vue'

const props = defineProps<{
  column: {
    text: string
    icon?: string
    tooltip?: string
    type: string
    value?: boolean
    color?: string
  }
}>()

const booleanClasses = computed(() => {
  const colorMap: Record<string, string> = {
    success: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    muted: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
  }

  return colorMap[props.column.color || 'muted'] || colorMap.muted
})
</script>
