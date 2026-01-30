<!--
 * LinkButton - Componente de botão de ação para tabelas
 *
 * Pode ser um link, botão com confirmação, ou botão com modal
 * Baseado na estrutura TableAction
 * 
 * NOTA: Este componente é legado. Prefira usar ActionRenderer
 * que usa o sistema de registro de componentes (ActionRegistry).
 -->
<template>
  <Button
    :variant="variant"
    :size="computedSize"
    :class="cn('gap-1.5', className)"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span class="text-xs">{{ label }}</span>
  </Button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

interface Props {
  action: TableAction
  size?: 'default' | 'sm' | 'lg' | 'icon'
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'sm'
})

const emit = defineEmits<{
  (e: 'click', action: TableAction): void
}>()

// Label
const label = computed(() => props.action.label)

// Usa composable para UI padronizada
const { variant, size: computedSize, iconComponent, iconClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})

// Handler de clique
const handleClick = () => {
  emit('click', props.action)
}
</script>
