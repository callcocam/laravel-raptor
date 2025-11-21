<!--
 * SlideoverForm - Renderiza formulário em slideover
 *
 * Componente especializado para exibir formulários editáveis em painel lateral
 -->
<template>
  <div class="flex-1 overflow-y-auto px-6 py-6">
    <FormRenderer
      :columns="columns"
      :errors="errors"
      v-model="formData"
      ref="formRef"
      @submit="emit('submit')"
    />
  </div>

  <!-- Footer com botões -->
  <div class="border-t px-6 py-4">
    <div class="flex justify-end gap-3">
      <Button variant="outline" @click="emit('cancel')">
        Cancelar
      </Button>
      <Button @click="emit('submit')" :disabled="isSubmitting">
        {{ isSubmitting ? 'Processando...' : confirmText }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import FormRenderer from '../../form/FormRenderer.vue'

interface Props {
  columns: any[]
  modelValue: Record<string, any>
  errors?: Record<string, string | string[]>
  isSubmitting?: boolean
  confirmText?: string
}

const props = withDefaults(defineProps<Props>(), {
  errors: () => ({}),
  isSubmitting: false,
  confirmText: 'Confirmar'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, any>): void
  (e: 'submit'): void
  (e: 'cancel'): void
}>()

const formRef = ref<InstanceType<typeof FormRenderer> | null>(null)
const formData = ref(props.modelValue)

defineExpose({
  formRef,
  formData
})
</script>
