<!--
 * SlideoverForm - Renderiza formulário em slideover com lógica de submit
 *
 * Componente especializado autocontido que gerencia:
 * - Estado do formulário (useForm do Inertia)
 * - Renderização dos campos
 * - Submit e validação
 * - Botões de ação
 -->
<template>
  <div class="flex-1 overflow-y-auto px-6 py-6">
    <FormRenderer :columns="columns" :errors="form.errors" v-model="formData" ref="formRef" @submit="handleSubmit" />
  </div>

  <!-- Footer com botões -->
  <div class="border-t px-6 py-4">
    <div class="flex justify-end gap-3">
      <Button variant="outline" @click="emit('cancel')" :disabled="form.processing">
        Cancelar
      </Button>
      <Button @click="handleSubmit" :disabled="form.processing">
        {{ form.processing ? 'Processando...' : confirmText }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import FormRenderer from '../../form/FormRenderer.vue'

interface Props {
  columns: any[]
  modelValue: Record<string, any>
  action: {
    url: string
    method: string
    confirm?: {
      confirmButtonText?: string
      closeModalOnSuccess?: boolean
    },
    inertia?: {
      only?: string[]
      preserveScroll?: boolean
      preserveState?: boolean
    }
  }
  confirmText?: string
}

const props = withDefaults(defineProps<Props>(), {
  confirmText: 'Confirmar'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, any>): void
  (e: 'success', data: any): void
  (e: 'error', errors: any): void
  (e: 'cancel'): void
}>()

// Referência ao FormRenderer
const formRef = ref<InstanceType<typeof FormRenderer> | null>(null)

// Dados do formulário (ref separado para v-model)
const formData = ref<Record<string, any>>(props.modelValue)

// Form do Inertia - gerencia automaticamente processing, errors, success
const form = useForm({}) 
const inertiaConfig = computed(() =>
  props.action.inertia ||
  {
    only: [],
    preserveScroll: true,
    preserveState: false

  }
);
// Handler para submit do formulário
const handleSubmit = () => {
  // Submit usando useForm do Inertia com transform para passar os dados atualizados
  form
    .transform(() => formData.value)
    .submit(
      props.action.method.toLowerCase() as 'post' | 'put' | 'patch' | 'delete',
      props.action.url,
      {
        preserveScroll: inertiaConfig.value.preserveScroll,
        preserveState: inertiaConfig.value.preserveState,
        onSuccess: (page) => {
          emit('success', page)
        },
        onError: (errors) => {
          // form.errors já foi populado automaticamente pelo Inertia
          emit('error', errors)
        }
      }
    )
}

// Limpa erros do formulário
const clearErrors = () => {
  form.clearErrors()
}

defineExpose({
  formRef,
  formData,
  form,
  clearErrors
})
</script>
