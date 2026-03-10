<!--
 * ActionCallback - Componente para executar ações via callback JavaScript
 *
 * Executa uma função JavaScript registrada no window sem fazer chamada ao backend
 * Útil para ações client-side como print, export, etc.
 -->
<template>
  <Button
    v-if="!isActionStyle"
    type="button"
    :variant="variant"
    :size="size"
    :class="cn('gap-1.5', className)"
    @click="handleClick"
  >
    <ActionIconBox v-if="iconComponent" :variant="iconBoxVariant">
      <component :is="iconComponent" />
    </ActionIconBox>
    <span class="text-xs text-foreground">{{ action.label }}</span>
  </Button>
  <button
    v-else
    type="button"
    :class="cn(actionStyle.buttonClasses, className)"
    @click="handleClick"
  >
    <div v-if="iconComponent" :class="actionStyle.iconWrapperClasses">
      <component :is="iconComponent" :class="actionStyle.iconClasses" />
    </div>
    <span :class="actionStyle.labelClasses">{{ action.label }}</span>
  </button>
</template>

<script setup lang="ts">
import { Button } from '~/components/ui/button'
import ActionIconBox from '~/components/ui/ActionIconBox.vue'
import { cn } from '~/lib/utils'
import { useAction } from '~/composables/useAction'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

interface Props {
  action: TableAction
  record?: any
  size?: 'default' | 'sm' | 'lg' | 'icon'
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'sm'
})

const emit = defineEmits<{
  (e: 'success'): void
  (e: 'error', error: any): void
}>()

const { executeCallback } = useAction()
const { variant, size, iconComponent, iconClasses, isActionStyle, actionStyle, iconBoxVariant } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})

// Handler de clique - executa callback
const handleClick = () => {
  if (!props.action.callback) {
    console.warn('ActionCallback: No callback specified')
    return
  }

  const success = executeCallback(props.action.callback, props.action, props.record)

  if (success) {
    emit('success')
  } else {
    emit('error', new Error(`Failed to execute callback: ${props.action.callback}`))
  }
}
</script>
