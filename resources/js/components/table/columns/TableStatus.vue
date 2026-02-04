<template>
  <div class="inline-flex items-center gap-2">
    <!-- Editable: Toggle Switch -->
    <button
      v-if="column.editable"
      type="button"
      role="switch"
      :aria-checked="isActive"
      :disabled="isUpdating"
      :class="[
        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent',
        'transition-colors duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2',
        'focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
        'disabled:cursor-not-allowed disabled:opacity-50',
        isActive ? activeClasses : inactiveClasses
      ]"
      :title="column.tooltip || (isActive ? 'Clique para desativar' : 'Clique para ativar')"
      @click="handleToggle"
    >
      <span
        :class="[
          'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-lg',
          'transform ring-0 transition duration-200 ease-in-out',
          isActive ? 'translate-x-5' : 'translate-x-0'
        ]"
      >
        <Icon
          v-if="isUpdating"
          is="Loader2"
          class="h-full w-full p-0.5 text-muted-foreground animate-spin"
        />
        <Icon
          v-else-if="icon"
          :is="icon"
          class="h-full w-full p-0.5 text-muted-foreground"
        />
      </span>
    </button>

    <!-- Read-only: Badge -->
    <span
      v-else
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
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Icon from '~/components/icon.vue'
import { toast } from 'vue-sonner'

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
    editable?: boolean
    executeUrl?: string
    statusKey?: string
    hasCallback?: boolean
    activeValues?: string[]
    [key: string]: any
  }
}>()

const isUpdating = ref(false)

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

const isActive = computed(() => {
  const val = String(value.value).toLowerCase()
  const activeValues = (props.column.activeValues || ['active', 'published', '1', 'true', 'ativo']).map(v => String(v).toLowerCase())
  return activeValues.includes(val)
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

const activeClasses = computed(() => {
  const colorMap: Record<string, string> = {
    success: 'bg-primary',
    warning: 'bg-yellow-500',
    danger: 'bg-destructive',
    info: 'bg-blue-500',
    muted: 'bg-muted-foreground',
  }
  
  return colorMap[color.value] || colorMap.success
})

const inactiveClasses = 'bg-muted'

const handleToggle = async () => {
  if (isUpdating.value || !props.column.executeUrl) return

  isUpdating.value = true
  
  const statusKey = props.column.statusKey || props.column.name
  const newValue = isActive.value ? 'inactive' : 'active'
  
  router.post(
    props.column.executeUrl,
    { 
      actionType: 'column',
      actionName: props.column.name,
      fieldName: props.column.name,
      record: props.record.id,
      [statusKey]: newValue,
    },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        // toast.success('Status atualizado com sucesso')
      },
      onError: (errors) => {
        console.error('Error updating status:', errors)
        toast.error('Erro ao atualizar status')
      },
      onFinish: () => {
        isUpdating.value = false
      },
    }
  )
}
</script>
