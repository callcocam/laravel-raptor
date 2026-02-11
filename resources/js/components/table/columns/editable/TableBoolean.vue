<template>
  <div class="inline-flex items-center gap-2">
    <button
      type="button"
      role="switch"
      :aria-checked="currentValue"
      :disabled="isUpdating"
      :class="[
        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50',
        currentValue ? 'bg-primary' : 'bg-muted'
      ]"
      :title="column.tooltip || (currentValue ? 'Clique para desmarcar' : 'Clique para marcar')"
      @click="handleToggle"
    >
      <span
        :class="[
          'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-lg transition',
          currentValue ? 'translate-x-5' : 'translate-x-0'
        ]"
      >
        <Icon
          v-if="isUpdating"
          is="Loader2"
          class="h-full w-full p-0.5 text-muted-foreground animate-spin"
        />
      </span>
    </button>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import Icon from '~/components/icon.vue'
import { useEditableColumn, getNestedValue } from '~/composables/useEditableColumn'

const props = defineProps<{
  record: Record<string, any>
  column: {
    name: string
    executeUrl?: string
    statusKey?: string
    tooltip?: string
    [key: string]: any
  }
}>()

const { isUpdating, submit } = useEditableColumn(props.record, props.column)

const currentValue = computed(() => Boolean(getNestedValue(props.record, props.column.name)))

function handleToggle() {
  if (isUpdating.value) return
  submit(!currentValue.value)
}
</script>
