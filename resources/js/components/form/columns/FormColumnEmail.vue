<!--
 * FormColumnEmail - Componente de campo de email
 *
 * Campo de input para emails com validação
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
      type="email"
      :placeholder="column.placeholder || column.label"
      :required="column.required"
      :disabled="column.disabled"
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
  placeholder?: string
  required?: boolean
  disabled?: boolean
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

const validateEmail = (email: string): boolean => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

const updateValue = (value: string | null) => {
  errorMessage.value = ''

  // Validação de email
  if (value && !validateEmail(value)) {
    errorMessage.value = 'Email inválido'
    return
  }

  emit('update:modelValue', value)
}
</script>
