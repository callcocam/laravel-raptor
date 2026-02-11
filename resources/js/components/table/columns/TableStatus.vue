<template>
  <div class="inline-flex items-center gap-2">
    <!-- Read-only: Badge (versão editável usa table-column-status-editable em columns/editable/) -->
    <span
      :class="[
        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
        'transition-colors duration-150',
        statusClasses
      ]"
      :title="column.tooltip"
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
    statusConfig?: Record<string, { label?: string; color?: string; icon?: string }>
    [key: string]: any
  }
}>()

// Get nested value from object using dot notation
const getNestedValue = (obj: any, path: string): any => {
  const keys = path.split('.')
  let result = obj
  
  for (const key of keys) {
    if (result && typeof result === 'object' && key in result) {
      result = result[key]
    } else {
      return ''
    }
  }
  
  return result ?? ''
}

const value = computed(() => getNestedValue(props.record, props.column.name))

const text = computed(() => {
  if (props.column.labelKey) {
    return getNestedValue(props.record, props.column.labelKey) || value.value
  }
  
  // If statusConfig exists, try to get label from it
  if (props.column.statusConfig && props.column.statusConfig[value.value]) {
    return props.column.statusConfig[value.value].label || value.value
  }
  
  return value.value
})

const color = computed<string>(() => {
  if (props.column.colorKey) {
    return String(getNestedValue(props.record, props.column.colorKey) || props.column.color || 'muted')
  }
  
  // If statusConfig exists, try to get color from it
  if (props.column.statusConfig && props.column.statusConfig[value.value]) {
    return props.column.statusConfig[value.value].color || props.column.color || 'muted'
  }
  
  return props.column.color || 'muted'
})

const icon = computed<string | undefined>(() => {
  if (props.column.iconKey) {
    const iconValue = getNestedValue(props.record, props.column.iconKey)
    return iconValue ? String(iconValue) : props.column.icon
  }
  
  // If statusConfig exists, try to get icon from it
  if (props.column.statusConfig && props.column.statusConfig[value.value]) {
    return props.column.statusConfig[value.value].icon || props.column.icon
  }
  
  return props.column.icon
})

const statusClasses = computed(() => {
  const colorMap: Record<string, string> = {
    success: 'bg-primary/10 text-primary border border-primary/20',
    warning: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-500 border border-yellow-500/20',
    danger: 'bg-destructive/10 text-destructive border border-destructive/20',
    info: 'bg-blue-500/10 text-blue-700 dark:text-blue-500 border border-blue-500/20',
    muted: 'bg-muted text-muted-foreground border border-border',
  }

  return colorMap[color.value] || colorMap.muted
})

const dotClasses = computed(() => {
  const colorMap: Record<string, string> = {
    success: 'bg-primary',
    warning: 'bg-yellow-500',
    danger: 'bg-destructive',
    info: 'bg-blue-500',
    muted: 'bg-muted-foreground',
  }

  return colorMap[color.value] || colorMap.muted
})
</script>
