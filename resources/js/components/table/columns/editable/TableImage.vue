<template>
  <div class="inline-flex items-center gap-2">
    <img
      v-if="imageUrl && !isEditing"
      :src="imageUrl"
      :alt="altText"
      :class="['object-contain border border-border cursor-pointer', column.rounded ? 'rounded-full' : 'rounded-md']"
      :style="imageStyle"
      @click="isEditing = true"
    />
    <div
      v-else-if="!imageUrl && !isEditing"
      :class="['flex items-center justify-center bg-muted text-muted-foreground cursor-pointer', column.rounded ? 'rounded-full' : 'rounded-md']"
      :style="imageStyle"
      @click="isEditing = true"
    >
      <Icon is="ImagePlus" class="h-6 w-6" />
    </div>
    <div v-else class="flex items-center gap-1 min-w-[160px]">
      <Input
        v-if="!isUpdating"
        v-model="localValue"
        type="url"
        class="h-8 text-sm flex-1"
        placeholder="https://..."
        @keydown.enter="handleSubmit"
      />
      <Button v-if="!isUpdating" size="sm" variant="ghost" class="h-8 px-2" @click="handleSubmit">
        <Icon is="Check" class="h-4 w-4" />
      </Button>
      <Button v-if="!isUpdating" size="sm" variant="ghost" class="h-8 px-2" @click="isEditing = false">
        <Icon is="X" class="h-4 w-4" />
      </Button>
      <Icon v-if="isUpdating" is="Loader2" class="h-4 w-4 animate-spin" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import Icon from '~/components/icon.vue'
import { useEditableColumn, getNestedValue } from '~/composables/useEditableColumn'

const props = defineProps<{
  record: Record<string, any>
  column: {
    name: string
    executeUrl?: string
    statusKey?: string
    tooltip?: string
    rounded?: boolean
    altKey?: string
    width?: number
    height?: number
    [key: string]: any
  }
}>()

const { isUpdating, submit } = useEditableColumn(props.record, props.column)

const imageUrl = computed(() => getNestedValue(props.record, props.column.name) || null)
const altText = computed(() => {
  if (props.column.altKey) return getNestedValue(props.record, props.column.altKey) || 'Image'
  return 'Image'
})

const imageStyle = computed(() => ({
  width: `${props.column.width ?? 40}px`,
  height: `${props.column.height ?? 40}px`,
}))

const isEditing = ref(false)
const localValue = ref(String(imageUrl.value ?? ''))

watch(imageUrl, (v) => {
  localValue.value = String(v ?? '')
})
watch(isEditing, (v) => {
  if (!v) localValue.value = String(imageUrl.value ?? '')
})

function handleSubmit() {
  submit(localValue.value)
  isEditing.value = false
}
</script>
