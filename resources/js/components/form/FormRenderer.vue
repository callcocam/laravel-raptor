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
      :error="errors[column.name]"
      v-model="formData[column.name]"
    />

    <!-- Slot para botões customizados -->
    <slot name="actions" :formData="formData" :isValid="isValid" :errors="errors">
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

// Erros de validação
const errors = ref<Record<string, string | string[]>>({})

// Atualiza erros quando a prop mudar
watch(() => props.errors, (newErrors) => {
  errors.value = newErrors || {}
}, { immediate: true, deep: true })

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

// Limpa erros de um campo específico quando o valor mudar
watch(formData, (newFormData, oldFormData) => {
  Object.keys(newFormData).forEach(key => {
    if (newFormData[key] !== oldFormData?.[key] && errors.value[key]) {
      delete errors.value[key]
    }
  })
}, { deep: true })

// Método para definir erros externamente
const setErrors = (newErrors: Record<string, string | string[]>) => {
  errors.value = newErrors
}

// Método para limpar todos os erros
const clearErrors = () => {
  errors.value = {}
}

// Expõe métodos para controle externo
defineExpose({
  formData,
  isValid,
  errors,
  submit: handleSubmit,
  setErrors,
  clearErrors,
})
</script>
