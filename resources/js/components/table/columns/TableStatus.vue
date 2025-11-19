<template>
  <div class="inline-flex items-center gap-2" :title="column.tooltip">
    <span
      :class="[
        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
        statusClasses
      ]"
    >
      <span
        v-if="column.showDot"
        :class="['h-1.5 w-1.5 rounded-full', dotClasses]"
      />
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
    color?: string
    showDot?: boolean
    labelKey?: string
    colorKey?: string
    iconKey?: string
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

const text = computed(() => {
  if (props.column.labelKey) {
    const keys = props.column.labelKey.split('.')
    let result = props.record
    for (const key of keys) {
      if (result && typeof result === 'object' && key in result) {
        result = result[key]
      } else {
        return value.value
      }
    }
    return result ?? value.value
  }
  return value.value
})

const color = computed<string>(() => {
  if (props.column.colorKey) {
    const keys = props.column.colorKey.split('.')
    let result = props.record
    for (const key of keys) {
      if (result && typeof result === 'object' && key in result) {
        result = result[key]
      } else {
        return props.column.color || 'muted'
      }
    }
    return String(result ?? props.column.color ?? 'muted')
  }
  return props.column.color || 'muted'
})

const icon = computed<string | undefined>(() => {
  if (props.column.iconKey) {
    const keys = props.column.iconKey.split('.')
    let result = props.record
    for (const key of keys) {
      if (result && typeof result === 'object' && key in result) {
        result = result[key]
      } else {
        return props.column.icon
      }
    }
    return result ? String(result) : props.column.icon
  }
  return props.column.icon
})

const statusClasses = computed(() => {
  const colorMap: Record<string, string> = {
    success: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    muted: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
  }

  return colorMap[color.value] || colorMap.muted
})

const dotClasses = computed(() => {
  const colorMap: Record<string, string> = {
    success: 'bg-green-600 dark:bg-green-400',
    warning: 'bg-yellow-600 dark:bg-yellow-400',
    danger: 'bg-red-600 dark:bg-red-400',
    info: 'bg-blue-600 dark:bg-blue-400',
    muted: 'bg-gray-600 dark:bg-gray-400',
  }

  return colorMap[color.value] || colorMap.muted
})
</script>
