<!--
 * FormRenderer - Renderiza um formulário completo
 *
 * Recebe um objeto de formulário com colunas e renderiza todos os campos
 * dinamicamente usando o FieldRenderer
 -->
<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <FieldRenderer
      v-for="(column, index) in columns"
      :key="column.name || index"
      :column="column"
      :error="formErrors[column.name]"
      v-model="formData[column.name]"
    />

    <!-- Slot para botões customizados -->
    <slot name="actions" :formData="formData" :isValid="isValid" :errors="formErrors">
      <!-- Botões padrão (opcional) -->
    </slot>
  </form>
</template>

<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import FieldRenderer from './columns/FieldRenderer.vue'

interface FormColumn {
  name: string
  label?: string
  component?: string
  required?: boolean
  [key: string]: any
}

interface Props {
  columns?: FormColumn[]
  modelValue?: Record<string, any>
  errors?: Record<string, string | string[]>
}

const props = withDefaults(defineProps<Props>(), {
  columns: () => [],
  modelValue: () => ({}),
  errors: () => ({}),
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, any>): void
  (e: 'submit', value: Record<string, any>): void
}>()

// Dados do formulário
const formData = reactive<Record<string, any>>({ ...props.modelValue })

// Erros de validação (computed para reagir às mudanças da prop)
const formErrors = computed(() => props.errors || {}) 

// Validação básica
const isValid = computed(() => {
  // Verifica se todos os campos obrigatórios estão preenchidos
  return props.columns.every(column => {
    if (column.required) {
      const value = formData[column.name]
      return value !== null && value !== undefined && value !== ''
    }
    return true
  })
})

// Handler de submit
const handleSubmit = () => {
  if (isValid.value) {
    emit('update:modelValue', formData)
    emit('submit', formData)
  }
}

// Sincroniza formData com o parent via v-model
watch(formData, (newFormData) => {
  emit('update:modelValue', newFormData)
}, { deep: true })

// Expõe métodos para controle externo
defineExpose({
  formData,
  isValid,
  errors: formErrors,
  submit: handleSubmit,
})
</script>
