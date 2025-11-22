<!--
 * ActionModalForm - Componente de ação com modal (form/table/infolist)
 *
 * Exibe um botão que, ao clicar, abre um modal com:
 * - FormRenderer (form): Para edição/criação com formulário
 * - TableOnlyRenderer (table): Para visualização de listas relacionadas
 * - InfoListRenderer (infolist): Para visualização de detalhes
 *
 * O tipo é detectado automaticamente pelo backend (ModalAction::detectColumnType())
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

    <DialogContent :class="dialogClasses">
      <DialogHeader>
        <DialogTitle>
          {{ action.label }}
        </DialogTitle>
        <DialogDescription v-if="action.tooltip">
          {{ action.tooltip }}
        </DialogDescription>
      </DialogHeader>

      <!-- Slot para conteúdo customizado ou renderiza baseado no columnType -->
      <slot name="content">
        <!-- Form: Renderiza FormRenderer com v-model e submit -->
        <div v-if="columnType === 'form' && hasFormColumns">
          <FormRenderer
            :columns="formColumns"
            :errors="formErrors"
            :gridColumns="gridColumns"
            :gap="gap"
            v-model="formData"
            ref="formRef"
            @submit="handleSubmit"
          />
        </div>

        <!-- Table: Renderiza TableOnlyRenderer (visualização apenas) -->
        <div v-else-if="columnType === 'table'">
          <TableOnlyRenderer :columns="formColumns" :data="tableData" />
        </div>

        <!-- InfoList: Renderiza InfoListRenderer (detalhes) -->
        <div v-else-if="columnType === 'infolist'">
          <InfoListRenderer :columns="formColumns" />
        </div>

        <!-- Conteúdo padrão se não houver colunas -->
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

      <!-- Footer: Apenas para formulários -->
      <DialogFooter v-if="$slots.footer || (columnType === 'form' && hasFormColumns)">
        <slot name="footer">
          <!-- Botões padrão para formulário -->
          <template v-if="columnType === 'form' && hasFormColumns">
            <Button variant="outline" @click="closeModal">
              Cancelar {{  isSubmitting }}
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
import TableOnlyRenderer from './../../../components/table/TableOnlyRenderer.vue'
import InfoListRenderer from './../../../components/infolist/InfoListRenderer.vue'
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
    columnType?: 'form' | 'table' | 'infolist'
    tableData?: any[] // Dados para renderização de tabela
    gridColumns?: string // Número de colunas do grid (padrão: 12)
    gap?: string // Espaçamento entre campos (padrão: 4)
    maxWidth?: string // Largura máxima do modal (sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl, full)
  }
  size?: 'default' | 'sm' | 'lg' | 'icon'
  record?: Record<string, any>
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
const formData = ref<Record<string, any>>(props.record || {})

// Erros de validação
const formErrors = ref<Record<string, string | string[]>>({})

// Tipo de coluna (form, table, ou infolist)
const columnType = computed(() => {
  return props.action.columnType || 'form'
})

// Colunas (pode ser form, table ou infolist)
const formColumns = computed(() => {
  return props.action.columns || []
})

// Dados para tabela (se columnType === 'table')
const tableData = computed(() => {
  return props.action.tableData || []
})

// Configurações de grid do formulário
const gridColumns = computed(() => {
  return props.action.gridColumns || '12'
})

const gap = computed(() => {
  return props.action.gap || '4'
})

// Classes do DialogContent (largura e altura)
const dialogClasses = computed(() => {
  const maxWidthMap: Record<string, string> = {
    'sm': 'sm:max-w-sm',
    'md': 'sm:max-w-md',
    'lg': 'sm:max-w-lg',
    'xl': 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    '3xl': 'sm:max-w-3xl',
    '4xl': 'sm:max-w-4xl',
    '5xl': 'sm:max-w-5xl',
    '6xl': 'sm:max-w-6xl',
    '7xl': 'sm:max-w-7xl',
    'full': 'sm:max-w-full',
  }

  const maxWidth = props.action.maxWidth || '4xl'
  
  return [
    maxWidthMap[maxWidth] || 'max-w-4xl',
    'max-h-[90vh]',
    'overflow-y-auto',
  ].join(' ')
})

// Verifica se há colunas
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
  if (columnType.value === 'form' && hasFormColumns.value) {
    isSubmitting.value = true
    formErrors.value = {} // Limpa erros anteriores

    // Pega o formData do FormRenderer (se existir ref)
    const dataToSubmit = formRef.value?.formData || formData.value 
 

    try {
      // Executa a action com os dados do formulário
      await actionComposable.execute({
        url: props.action.url,
        method: props.action.method as any,
        successMessage: '',
        onSuccess: (data) => {
          emit('submit', data)
          emit('success', data)

          // Fecha o modal apenas se closeModalOnSuccess for true (padrão)
          if (props.action.confirm?.closeModalOnSuccess ?? true) {
            closeModal()
          }
        },
        onError: (error) => {  
          // Captura erros de validação do Inertia (objeto com campo: mensagem)
          if (error && typeof error === 'object') {
            // Converte para o formato esperado pelo FormRenderer
            const validationErrors: Record<string, string | string[]> = {}
            Object.keys(error).forEach(key => {
              const errorValue = error[key]
              // Se for array, pega o primeiro erro
              validationErrors[key] = Array.isArray(errorValue) ? errorValue[0] : errorValue
            })
            formErrors.value = validationErrors
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
