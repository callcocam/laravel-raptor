<template>
  <div class="inline-flex items-center gap-2" :title="column.tooltip">
    <span
      :class="[
        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
        booleanClasses
      ]"
    >
      <Icon v-if="icon" :is="icon" class="h-3 w-3" />
      {{ text }}
    </span>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import Icon from '~/components/icon.vue'

const props = defineProps<{
  record: Record<string, any>
  column: {
    name: string
    icon?: string
    tooltip?: string
    type?: string
    trueLabel?: string
    falseLabel?: string
    trueColor?: string
    falseColor?: string
    trueIcon?: string
    falseIcon?: string
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
      return false
    }
  }
  
  return Boolean(result)
})

const text = computed(() => {
  return value.value 
    ? (props.column.trueLabel || 'Sim') 
    : (props.column.falseLabel || 'Não')
})

const icon = computed(() => {
  return value.value 
    ? props.column.trueIcon 
    : props.column.falseIcon
})

const color = computed(() => {
  return value.value 
    ? (props.column.trueColor || 'success') 
    : (props.column.falseColor || 'muted')
})

const booleanClasses = computed(() => {
  const colorMap: Record<string, string> = {
    success: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    muted: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
  }

  return colorMap[color.value] || colorMap.muted
})
</script>
