<!--
 * ActionModalForm - Componente de ação com modal (form/table/infolist)
 *
 * Exibe um botão que, ao clicar, abre um modal com:
 * - ModalForm: Para edição/criação com formulário
 * - ModalTable: Para visualização de listas relacionadas
 * - ModalInfo: Para visualização de detalhes
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
        :size="computedSize"
        class="gap-1.5 btn-gradient"
        @click="handleTriggerClick"
      >
        <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
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
        <!-- Form Mode -->
        <ModalForm
          v-if="columnType === 'form' && hasFormColumns"
          v-model="formData"
          :columns="formColumns"
          :action="{
            ...action,
            confirm: action.confirm ?? undefined
          }"
          :confirm-text="action.confirm?.confirmButtonText || 'Confirmar'"
          :grid-columns="gridColumns"
          :gap="gap"
          ref="modalFormRef"
          @success="handleSuccess"
          @error="handleError"
          @cancel="closeModal"
        />

        <!-- Table Mode -->
        <ModalTable
          v-else-if="columnType === 'table'"
          :columns="formColumns"
          :data="tableData"
        />

        <!-- InfoList Mode -->
        <ModalInfo
          v-else-if="columnType === 'infolist'"
          :columns="formColumns"
          :value="formData"
          :name="props.action.name"
        />

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
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import * as LucideIcons from 'lucide-vue-next'
import ModalForm from '../modal/ModalForm.vue'
import ModalTable from '../modal/ModalTable.vue'
import ModalInfo from '../modal/ModalInfo.vue'
import { useActionConfig } from '~/composables/useActionConfig'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

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

// Dados iniciais do formulário
const formData = ref<Record<string, any>>(props.record || {})

// Referência ao ModalForm (para limpar erros, etc)
const modalFormRef = ref<InstanceType<typeof ModalForm> | null>(null)

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

// Usa o composable para configurações comuns
const {
  gridColumns,
  gap,
  dialogClasses,
  hasFormColumns,
} = useActionConfig({
  action: props.action,
  columns: formColumns
})

// Usa composable para UI padronizada (variant, iconComponent, iconClasses)
const { variant, size: computedSize, iconComponent, iconClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})

// Handler para click no trigger
const handleTriggerClick = () => {
  emit('click')
}

// Handler para sucesso do formulário (vem do ModalForm)
const handleSuccess = (page: any) => {
  emit('submit', formData.value)
  emit('success', page)

  // Fecha o modal apenas se closeModalOnSuccess for true (padrão)
  if (props.action.confirm?.closeModalOnSuccess ?? true) {
    closeModal()
  }

  // Emite evento de click para compatibilidade
  emit('click', formData.value)
}

// Handler para erro do formulário (vem do ModalForm)
const handleError = (errors: any) => {
  emit('error', errors)
}

// Fecha o modal
const closeModal = () => {
  isOpen.value = false
  // Limpa erros do ModalForm se existir
  if (modalFormRef.value) {
    modalFormRef.value.clearErrors()
  }
}

// Watch para emitir eventos quando o modal abre/fecha
watch(isOpen, (newValue) => {
  if (newValue) {
    emit('open')
  } else {
    emit('close')
    // Limpa erros ao fechar
    if (modalFormRef.value) {
      modalFormRef.value.clearErrors()
    }
  }
})

// Expõe métodos para controle externo
defineExpose({
  open: () => { isOpen.value = true },
  close: closeModal,
  isOpen,
  formData, // Dados iniciais do formulário
  modalFormRef, // Referência ao ModalForm (para acesso ao form do Inertia se necessário)
})
</script>
