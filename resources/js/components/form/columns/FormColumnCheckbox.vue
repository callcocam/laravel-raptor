<!--
 * FormColumnCheckbox - Componente de campo checkbox
 *
 * Campo checkbox para valores booleanos
 -->
<template>
  <div class="flex items-center space-x-2">
    <Checkbox
      :id="column.name"
      :name="column.name"
      :required="column.required"
      :checked="internalValue"
      @update:checked="updateValue"
    />
    <div class="grid gap-1.5 leading-none">
      <Label
        :for="column.name"
        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
      >
        {{ column.label }}
        <span v-if="column.required" class="text-destructive">*</span>
      </Label>
      <p v-if="column.description" class="text-sm text-muted-foreground">
        {{ column.description }}
      </p>
      <p v-if="column.tooltip" class="text-xs text-muted-foreground">
        {{ column.tooltip }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'

interface FormColumn {
  name: string
  label?: string
  required?: boolean
  description?: string
  tooltip?: string
  default?: boolean
}

interface Props {
  column: FormColumn
  modelValue?: boolean | null
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

const internalValue = computed(() => {
  if (props.modelValue !== null) {
    return props.modelValue
  }
  return props.column.default || false
})

const updateValue = (value: boolean) => {
  emit('update:modelValue', value)
}
</script>
