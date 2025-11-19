<!--
 * FormColumnDate - Componente de campo de data
 *
 * Campo de seleção de data/datetime
 -->
<template>
  <div class="space-y-2">
    <Label v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </Label>

    <Input
      :id="column.name"
      :name="column.name"
      :type="column.withTime ? 'datetime-local' : 'date'"
      :required="column.required"
      :disabled="column.disabled"
      :min="column.minDate"
      :max="column.maxDate"
      :modelValue="modelValue"
      @update:modelValue="updateValue"
      :class="hasError ? 'border-destructive' : ''"
    />

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
import { Input } from '@/components/ui/input'

interface FormColumn {
  name: string
  label?: string
  required?: boolean
  disabled?: boolean
  minDate?: string
  maxDate?: string
  withTime?: boolean
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

const updateValue = (value: string | null) => {
  errorMessage.value = ''

  // Validação de data mínima
  if (props.column.minDate && value && value < props.column.minDate) {
    errorMessage.value = 'Data menor que o mínimo permitido'
    return
  }

  // Validação de data máxima
  if (props.column.maxDate && value && value > props.column.maxDate) {
    errorMessage.value = 'Data maior que o máximo permitido'
    return
  }

  emit('update:modelValue', value)
}
</script>
