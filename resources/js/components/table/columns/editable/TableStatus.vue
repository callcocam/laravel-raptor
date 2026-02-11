<template>
  <div class="inline-flex items-center gap-2">
    <!-- Editable: Toggle Switch (este componente é carregado apenas quando column.editable) -->
    <button
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
    executeUrl?: string
    statusKey?: string
    activeValues?: string[]
    statusConfig?: Record<string, { label?: string; color?: string; icon?: string }>
    [key: string]: any
  }
}>()

const isUpdating = ref(false)

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

const icon = computed<string | undefined>(() => {
  if (props.column.statusConfig && props.column.statusConfig[value.value]) {
    return props.column.statusConfig[value.value].icon || props.column.icon
  }
  return props.column.icon
})

const isActive = computed(() => {
  const val = String(value.value).toLowerCase()
  const activeValues = (props.column.activeValues || ['active', 'published', '1', 'true', 'ativo']).map((v: string) =>
    String(v).toLowerCase()
  )
  return activeValues.includes(val)
})

const activeClasses = computed(() => {
  const colorMap: Record<string, string> = {
    success: 'bg-primary',
    warning: 'bg-yellow-500',
    danger: 'bg-destructive',
    info: 'bg-blue-500',
    muted: 'bg-muted-foreground',
  }
  if (props.column.statusConfig && props.column.statusConfig[value.value]) {
    const c = props.column.statusConfig[value.value].color || 'success'
    return colorMap[c] || colorMap.success
  }
  return colorMap.success
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
      onError: () => {
        toast.error('Erro ao atualizar status')
      },
      onFinish: () => {
        isUpdating.value = false
      },
    }
  )
}
</script>
