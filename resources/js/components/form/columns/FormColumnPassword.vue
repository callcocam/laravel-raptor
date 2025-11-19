<!--
 * FormColumnPassword - Componente de campo de senha
 *
 * Campo de input para senha com toggle de visibilidade
 -->
<template>
  <div class="space-y-2">
    <Label v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </Label>

    <div class="relative">
      <Input
        :id="column.name"
        :name="column.name"
        :type="showPassword ? 'text' : 'password'"
        :placeholder="column.placeholder || column.label"
        :required="column.required"
        :disabled="column.disabled"
        :minlength="column.minLength"
        :modelValue="modelValue"
        @update:modelValue="updateValue"
        :class="hasError ? 'border-destructive' : ''"
        class="pr-10"
      />

      <!-- Toggle de visibilidade -->
      <button
        v-if="column.showToggle"
        type="button"
        @click="showPassword = !showPassword"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
      >
        <EyeOff v-if="showPassword" class="h-4 w-4" />
        <Eye v-else class="h-4 w-4" />
      </button>
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
import { Input } from '@/components/ui/input'
import { Eye, EyeOff } from 'lucide-vue-next'

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  minLength?: number
  showToggle?: boolean
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
const showPassword = ref(false)
const hasError = computed(() => !!errorMessage.value)

const updateValue = (value: string | null) => {
  errorMessage.value = ''

  // Validação de comprimento mínimo
  if (props.column.minLength && value && value.length < props.column.minLength) {
    errorMessage.value = `Mínimo de ${props.column.minLength} caracteres`
    return
  }

  emit('update:modelValue', value)
}
</script>
