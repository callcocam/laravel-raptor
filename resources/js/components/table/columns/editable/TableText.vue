<template>
  <div class="inline-flex items-center gap-2 min-w-[120px]">
    <Input
      v-if="!isUpdating"
      :model-value="localValue"
      class="h-8 text-sm"
      :title="column.tooltip"
      @update:model-value="localValue = $event"
      @blur="handleSubmit"
      @keydown.enter="handleSubmit"
    />
    <span v-else class="text-sm text-muted-foreground flex items-center gap-1">
      <Icon is="Loader2" class="h-3 w-3 animate-spin" />
      Salvando...
    </span>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import Icon from '~/components/icon.vue'
import { useEditableColumn, getNestedValue } from '~/composables/useEditableColumn'

const props = defineProps<{
  record: Record<string, any>
  column: { name: string; executeUrl?: string; statusKey?: string; tooltip?: string; limit?: number; [key: string]: any }
}>()

const { isUpdating, submit } = useEditableColumn(props.record, props.column)

const value = computed(() => getNestedValue(props.record, props.column.name))
const localValue = ref(String(value.value ?? ''))

watch(value, (v) => {
  localValue.value = String(v ?? '')
})

function handleSubmit() {
  if (String(localValue.value) === String(value.value)) return
  submit(localValue.value)
}
</script>
