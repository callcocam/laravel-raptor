<!--
 * FormFieldHidden - Hidden input field
 *
 * Simple replacement for FormColumnHidden
 -->
<template>
  <input
    :id="column.name"
    :name="column.name"
    type="hidden"
    :value="internalValue"
  />
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'

interface FormColumn {
  name: string
  default?: any
}

interface Props {
  column: FormColumn
  modelValue?: any
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
}>()

const internalValue = computed(() => {
  if (props.modelValue !== null && props.modelValue !== undefined) {
    return props.modelValue
  }
  return props.column.default
})

onMounted(() => {
  if (!props.modelValue && props.column.default) {
    emit('update:modelValue', props.column.default)
  }
})
</script>
