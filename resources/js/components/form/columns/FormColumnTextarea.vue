<!--
 * FormColumnTextarea - Componente de campo textarea
 *
 * Campo de textarea para textos longos
 -->
<template>
  <div class="space-y-2">
    <Label v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </Label>

    <Textarea
      :id="column.name"
      :name="column.name"
      :placeholder="column.placeholder || column.label"
      :required="column.required"
      :disabled="column.disabled"
      :rows="column.rows || 3"
      :maxlength="column.maxLength"
      :modelValue="modelValue"
      @update:modelValue="updateValue"
      :class="hasError ? 'border-destructive' : ''"
    />

    <!-- Contador de caracteres -->
    <div v-if="column.maxLength" class="flex justify-between text-xs text-muted-foreground">
      <span>{{ charCount }} / {{ column.maxLength }}</span>
    </div>

    <!-- Mensagem de erro -->
    <p v-if="errorMessage" class="text-sm text-destructive">
      {{ errorMessage }}
    </p>

    <!-- Dica/tooltip -->
    <p v-if="column.tooltip && !errorMessage" class="text-xs text-muted-foreground">
      {{ column.tooltip }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea/index'

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  rows?: number
  maxLength?: number
  tooltip?: string
}

interface Props {
  column: FormColumn
  modelValue?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

const errorMessage = ref('')
const hasError = computed(() => !!errorMessage.value)

const charCount = computed(() => {
  return props.modelValue?.length || 0
})

const updateValue = (value: string | null) => {
  errorMessage.value = ''

  // Valida maxLength
  if (props.column.maxLength && value && value.length > props.column.maxLength) {
    errorMessage.value = `Máximo de ${props.column.maxLength} caracteres`
    return
  }

  emit('update:modelValue', value)
}
</script>
