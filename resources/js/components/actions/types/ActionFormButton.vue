<!--
 * ActionFormButton - Componente de botão para ações de formulário
 *
 * Renderiza botões de submit e cancel em formulários
 * Suporta estado de loading e navegação de cancelamento
 -->
<template>
  <Button
    :type="buttonType"
    :variant="computedVariant"
    :size="computedSize"
    :as-child="asChild"
    :disabled="isDisabled"
    :class="cn('gap-1.5', className)"
    @click="handleClick"
  >
    <component v-if="iconComponent && !isProcessing" :is="iconComponent" :class="iconClasses" />
    <span class="text-xs">{{ displayLabel }}</span>
  </Button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

interface Props {
  action: TableAction & { processing?: boolean }
  variant?: 'default' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
  asChild?: boolean
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'sm',
  asChild: false
})

const emit = defineEmits<{
  (e: 'click'): void
}>()

// Determina o tipo de botão (submit ou button)
const buttonType = computed(() => {
  return props.action.actionType === 'submit' ? 'submit' : 'button'
})

// Determina se o botão está processando
const isProcessing = computed(() => {
  return props.action.processing && props.action.actionType === 'submit'
})

// Determina se o botão está desabilitado
const isDisabled = computed(() => {
  return isProcessing.value
})

// Label exibido (muda durante processamento)
const displayLabel = computed(() => {
  if (isProcessing.value) {
    return props.action.processingLabel || 'Salvando...'
  }
  return props.action.label || 'Ação'
})

// Usa composable para variant, size, iconComponent, iconClasses
const { variant: baseVariant, size: computedSize, iconComponent, iconClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm',
  defaultVariant: props.variant
})

// Variant com overrides para submit/cancel
const computedVariant = computed(() => {
  // Se tem variant explícito, usa
  if (props.variant) return props.variant
  if (props.action.variant) return props.action.variant
  
  // Padrões por tipo de ação se não houver cor
  if (!props.action.color) {
    const actionTypeDefaults: Record<string, any> = {
      'submit': 'default',
      'cancel': 'outline'
    }
    return actionTypeDefaults[props.action.actionType || ''] || 'default'
  }

  return baseVariant.value
})

// Handler de clique
const handleClick = () => {
  if (props.action.actionType === 'submit') {
    // Submit é tratado pelo form, não fazemos nada aqui
    emit('click')
    return
  }

  if (props.action.actionType === 'cancel') {
    if (props.action.url && typeof props.action.url === 'string') {
      // Se a URL começa com javascript:, executa usando Function constructor (mais seguro que eval)
      if (props.action.url.startsWith('javascript:')) {
        try {
          const code = props.action.url.replace('javascript:', '')
          const fn = new Function(code)
          fn()
        } catch (error) {
          console.error('Error executing action URL:', error)
        }
      } else {
        // Caso contrário, navega para a URL
        router.visit(props.action.url)
      }
    } else {
      // Se não há URL, volta na história
      window.history.back()
    }
  }

  emit('click')
}
</script>
