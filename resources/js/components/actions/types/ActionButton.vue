<!--
 * ActionButton - Componente de botão de ação padrão
 *
 * Renderiza um botão com ícone e label
 * Suporta diferentes variantes e cores
 * Executa ações via Inertia.js router seguindo o padrão do Action.php
 -->
<template>
  <Button 
    :variant="computedVariant" 
    :size="computedSize" 
    :as-child="asChild" 
    :class="cn('gap-1.5', className)"
    :disabled="isExecuting"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span class="text-xs">{{ action.label }}</span>
  </Button>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { useAction } from '~/composables/useAction'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

interface Props {
  action: TableAction
  record?: any
  variant?: 'default' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
  asChild?: boolean
  className?: string
  column?: Record<string, any>
  [key: string]: any
}

const props = withDefaults(defineProps<Props>(), {
  size: 'sm',
  asChild: false
})

const emit = defineEmits<{
  (e: 'click', event: Event): void
  (e: 'success', data: any): void
  (e: 'error', error: any): void
}>()

const { execute, isExecuting } = useAction()
const { variant: computedVariant, size: computedSize, iconComponent, iconClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm',
  defaultVariant: props.variant
})

/**
 * Handler de clique - executa a ação via Inertia.js
 * 
 * Segue o padrão do Action.php:
 * - Usa a URL gerada pelo Action.php (rota personalizada ou /execute padrão)
 * - Respeita o método HTTP (GET, POST, PUT, PATCH, DELETE)
 * - Preserva scroll e state conforme configurado
 * - Passa o record ID se disponível
 */
const handleClick = async (event: Event) => {
  // Emite evento de clique
  emit('click', event)

  // Verifica se há URL configurada
  if (!props.action.url) {
    console.warn('ActionButton: No URL specified for action', props.action)
    return
  }

  // Prepara os dados para enviar
  const formData: Record<string, any> = {}
  
  // Se tiver record, adiciona o ID
  if (props.record) {
    formData.record_id = props.record.id || props.record
  }

  // Adiciona o nome da ação
  if (props.action.name) {
    formData.action = props.action.name
    formData.actionName = props.action.name
  }

  // Adiciona o nome do campo se disponível
  if (props.column && props.column.name) {
    formData.fieldName = props.column.name
  }

  formData.actionType = props.action.actionType || 'api'
  // Configura a ação seguindo o padrão do Action.php
  const actionConfig = {
    url: props.action.url,
    method: (props.action.method?.toUpperCase() || 'POST') as 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
    actionType: (props.action.actionType === 'submit' || props.action.actionType === 'cancel' ? 'api' : props.action.actionType || 'api') as 'link' | 'api' | 'callback' | 'modal' | 'table',
    inertia: {
      preserveScroll: props.action.inertia?.preserveScroll ?? true,
      preserveState: props.action.inertia?.preserveState ?? false,
      only: props.action.inertia?.only || []
    },
    successMessage: props.action.successMessage,
    errorMessage: props.action.errorMessage,
    onSuccess: (data: any) => {
      emit('success', data)
    },
    onError: (error: any) => {
      emit('error', error)
    }
  }

  // Executa a ação
  await execute(actionConfig, formData)
}
</script>
