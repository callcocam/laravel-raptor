<!--
 * FormRenderer - Renderiza um formulário completo
 *
 * Recebe um objeto de formulário com colunas e renderiza todos os campos
 * dinamicamente usando o FieldRenderer com suporte a grid layout
 -->
<template>
  <form @submit.prevent="handleSubmit" :class="formClasses">
    <div
      v-for="(column, index) in columns"
      :key="column.name || index"
      :class="getColumnClasses(column)"
      :style="getColumnStyles(column)"
    >
      <FieldRenderer
        :column="column"
        :error="formErrors[column.name]"
        v-model="formData[column.name]"
      />
    </div>

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
  columnSpan?: string
  gridColumns?: string
  order?: number
  gap?: string
  responsive?: {
    grid?: { sm?: string; md?: string; lg?: string; xl?: string }
    span?: { sm?: string; md?: string; lg?: string; xl?: string }
  }
  [key: string]: any
}

interface Props {
  columns?: FormColumn[]
  modelValue?: Record<string, any>
  errors?: Record<string, string | string[]>
  gridColumns?: string  // Número de colunas do grid do formulário (padrão: 2)
  gap?: string          // Espaçamento entre campos (padrão: 4)
}

const props = withDefaults(defineProps<Props>(), {
  columns: () => [],
  modelValue: () => ({}),
  errors: () => ({}),
  gridColumns: '12',
  gap: '4',
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

// Classes do formulário (grid layout)
const formClasses = computed(() => {
  return [
    'grid',
    `grid-cols-1`,
    `md:grid-cols-${props.gridColumns}`,
    `gap-x-${props.gap}`,
    `gap-y-4`, // Espaçamento vertical entre campos
  ].join(' ')
})

// Gera classes do grid para cada coluna
const getColumnClasses = (column: FormColumn) => {
  const classes: string[] = []
  
  // Mobile: sempre full width (col-span-1 dentro de grid-cols-1)
  classes.push('col-span-1')
  
  // Column span padrão (desktop - md:)
  if (column.columnSpan) {
    // Se for 'full', usa col-span-full sem breakpoint
    if (column.columnSpan === 'full') {
      classes.push('md:col-span-full')
    } else {
      classes.push(`md:col-span-${column.columnSpan}`)
    }
  }
  
  // Column span responsivo
  if (column.responsive?.span) {
    const { sm, md, lg, xl } = column.responsive.span
    if (sm) classes.push(`sm:col-span-${sm}`)
    if (md) classes.push(`md:col-span-${md}`)
    if (lg) classes.push(`lg:col-span-${lg}`)
    if (xl) classes.push(`xl:col-span-${xl}`)
  }
  
  return classes.join(' ')
}

// Gera estilos inline para ordem (se especificado)
const getColumnStyles = (column: FormColumn) => {
  if (column.order !== undefined) {
    return { order: column.order }
  }
  return {}
}

// Expõe métodos para controle externo
defineExpose({
  formData,
  isValid,
  errors: formErrors,
  submit: handleSubmit,
})
</script>
