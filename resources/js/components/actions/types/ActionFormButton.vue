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
    <span>{{ displayLabel }}</span>
  </Button>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import * as LucideIcons from 'lucide-vue-next'
import type { TableAction } from '~/types/table'

interface Props {
  action: TableAction & { processing?: boolean }
  variant?: 'default' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
  asChild?: boolean
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'default',
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

// Variant computado - usa prop ou mapeia da cor/action.variant
const computedVariant = computed(() => {
  if (props.variant) return props.variant
  if (props.action.variant) return props.action.variant
  
  const colorMap: Record<string, any> = {
    'green': 'default',
    'blue': 'default',
    'red': 'destructive',
    'yellow': 'outline',
    'gray': 'secondary',
    'default': 'default'
  }

  // Padrões por tipo de ação
  const actionTypeDefaults: Record<string, string> = {
    'submit': 'default',
    'cancel': 'outline'
  }

  if (props.action.color) {
    return colorMap[props.action.color] || 'default'
  }

  return actionTypeDefaults[props.action.actionType || ''] || 'default'
})

// Size computado
const computedSize = computed(() => props.size)

// Classes do ícone
const iconClasses = computed(() => {
  const sizeMap: Record<string, string> = {
    'sm': 'h-3 w-3',
    'default': 'h-4 w-4',
    'lg': 'h-5 w-5',
    'icon': 'h-4 w-4'
  }
  return sizeMap[props.size] || 'h-4 w-4'
})

// Componente do ícone dinâmico
const iconComponent = computed(() => {
  if (!props.action.icon) return null

  // Tenta obter o ícone do Lucide
  const IconComponent = (LucideIcons as any)[props.action.icon]

  if (!IconComponent) {
    console.warn(`Icon "${props.action.icon}" not found in lucide-vue-next`)
    return null
  }

  return h(IconComponent)
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
