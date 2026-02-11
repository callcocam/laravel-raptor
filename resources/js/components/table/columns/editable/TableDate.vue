<template>
  <div class="inline-flex items-center gap-2 min-w-[140px]">
    <Input
      v-if="!isUpdating"
      v-model="localValue"
      type="date"
      class="h-8 text-sm"
      :title="column.tooltip"
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
  column: { name: string; executeUrl?: string; statusKey?: string; tooltip?: string; format?: string; [key: string]: any }
}>()

const { isUpdating, submit } = useEditableColumn(props.record, props.column)

const rawValue = computed(() => getNestedValue(props.record, props.column.name))
const toInputDate = (v: any) => {
  if (!v) return ''
  const d = new Date(v)
  return isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10)
}
const localValue = ref(toInputDate(rawValue.value))

watch(rawValue, (v) => {
  localValue.value = toInputDate(v)
})

function handleSubmit() {
  if (localValue.value === toInputDate(rawValue.value)) return
  submit(localValue.value || '')
}
</script>
