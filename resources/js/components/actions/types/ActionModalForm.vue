<!--
 * ActionModalForm - Componente de ação com modal e formulário
 *
 * Exibe um botão que, ao clicar, abre um modal com formulário
 * Útil para importação, exportação, formulários complexos, etc.
 *
 * Usa Dialog da shadcn-vue para seguir o padrão do projeto
 * Registrado como 'action-modal' e 'action-modal-form' no ActionRegistry
 -->
<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button
        :variant="variant"
        :size="size"
        class="gap-1 h-7 px-2.5"
        @click="handleTriggerClick"
      >
        <component v-if="iconComponent" :is="iconComponent" class="h-3 w-3" />
        <span class="text-xs">{{ action.label }}</span>
      </Button>
    </DialogTrigger>

    <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ action.label }}
        </DialogTitle>
        <DialogDescription v-if="action.tooltip">
          {{ action.tooltip }}
        </DialogDescription>
      </DialogHeader>

      <!-- Slot para conteúdo customizado ou formulário -->
      <slot name="content">
        <!-- Se houver colunas de formulário, renderiza o FormRenderer -->
        <FormRenderer
          v-if="hasFormColumns"
          :columns="formColumns"
          :errors="formErrors"
          v-model="formData"
          ref="formRef"
        />

        <!-- Conteúdo padrão se não houver formulário -->
        <div v-else class="text-center py-12">
          <component
            v-if="iconComponent"
            :is="iconComponent"
            class="h-12 w-12 mx-auto text-muted-foreground mb-4"
          />
          <p class="text-muted-foreground">
            Modal de {{ action.label }}
          </p>
          <p class="text-sm text-muted-foreground mt-2">
            URL: {{ action.url }}
          </p>
        </div>
      </slot>

      <!-- Footer -->
      <DialogFooter v-if="$slots.footer || hasFormColumns">
        <slot name="footer">
          <!-- Botões padrão para formulário -->
          <template v-if="hasFormColumns">
            <Button variant="outline" @click="closeModal">
              Cancelar
            </Button>
            <Button @click="handleSubmit" :disabled="isSubmitting">
              {{ isSubmitting ? 'Processando...' : (action.confirm?.confirmButtonText || 'Confirmar') }}
            </Button>
          </template>
        </slot>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, h, watch } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import * as LucideIcons from 'lucide-vue-next'
import FormRenderer from './../../../components/form/FormRenderer.vue'
import { useAction } from '~/composables/useAction'
import type { TableAction } from '~/types/table'

// Composable para executar actions
const actionComposable = useAction()

interface FormColumn {
  name: string
  label?: string
  component?: string
  required?: boolean
  [key: string]: any
}

interface Props {
  action: TableAction & {
    columns?: FormColumn[]
  }
  size?: 'default' | 'sm' | 'lg' | 'icon'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'sm'
})

const emit = defineEmits<{
  (e: 'click', formData?: Record<string, any>): void
  (e: 'open'): void
  (e: 'close'): void
  (e: 'submit', formData: Record<string, any>): void
  (e: 'success', data: any): void
  (e: 'error', error: any): void
}>()

// Estado do modal
const isOpen = ref(false)
const isSubmitting = ref(false)

// Referência ao FormRenderer
const formRef = ref<InstanceType<typeof FormRenderer> | null>(null)

// Dados do formulário (usando ref para permitir v-model)
const formData = ref<Record<string, any>>({})

// Erros de validação
const formErrors = ref<Record<string, string | string[]>>({})

// Colunas do formulário
const formColumns = computed(() => {
  return props.action.columns || []
})

// Verifica se há colunas de formulário
const hasFormColumns = computed(() => {
  return formColumns.value.length > 0
})

// Mapeia cor para variant do shadcn
const variant = computed(() => {
  const colorMap: Record<string, any> = {
    'green': 'default',
    'blue': 'default',
    'red': 'destructive',
    'yellow': 'warning',
    'gray': 'secondary',
    'default': 'default'
  }

  return colorMap[props.action.color || 'default'] || 'default'
})

// Componente do ícone dinâmico
const iconComponent = computed(() => {
  if (!props.action.icon) return null

  const IconComponent = (LucideIcons as any)[props.action.icon]

  if (!IconComponent) {
    console.warn(`Icon "${props.action.icon}" not found in lucide-vue-next`)
    return null
  }

  return h(IconComponent)
})

// Handler para click no trigger
const handleTriggerClick = () => {
  emit('click')
}

// Handler para submit do formulário
const handleSubmit = async () => {
  if (hasFormColumns.value) {
    isSubmitting.value = true
    formErrors.value = {} // Limpa erros anteriores

    // Pega o formData do FormRenderer (se existir ref)
    const dataToSubmit = formRef.value?.formData || formData.value
 

    try {
      // Executa a action com os dados do formulário
      await actionComposable.execute({
        url: props.action.url,
        method: props.action.method as any,
        successMessage: props.action.confirm?.successMessage || 'Operação realizada com sucesso!',
        onSuccess: (data) => {
          emit('submit', data)
          emit('success', data)
          closeModal()
        },
        onError: (error) => { 

          // Captura erros de validação (422)
          if (error.errors) {
            formErrors.value = error.errors
          }

          emit('error', error)
        }
      }, dataToSubmit)

      // Emite evento de click para compatibilidade
      emit('click', formData.value)

    } finally {
      isSubmitting.value = false
    }
  } else {
    emit('click')
  }
}

// Fecha o modal
const closeModal = () => {
  isOpen.value = false
}

// Watch para emitir eventos quando o modal abre/fecha e limpar erros
watch(isOpen, (newValue) => {
  if (newValue) {
    emit('open')
  } else {
    emit('close')
    // Limpa erros ao fechar
    formErrors.value = {}
  }
})

// Expõe métodos para controle externo
defineExpose({
  open: () => { isOpen.value = true },
  close: closeModal,
  isOpen,
  formData,
})
</script>
