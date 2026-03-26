<!--
 * ActionIconBox - Wrapper para ícones em botões (padrão plannerate)
 *
 * Envolve o ícone em uma caixa com cantos arredondados, consistente em todos os tipos de botão.
 * Variantes: default (verde), outline (borda), destructive (vermelho)
 -->
<template>
  <div :class="wrapperClasses">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '~/lib/utils'

type Variant = 'default' | 'outline' | 'destructive' | 'secondary' | 'ghost'

const props = withDefaults(
  defineProps<{
    variant?: Variant
    class?: string
  }>(),
  { variant: 'default' }
)

const wrapperClasses = computed(() => {
  const base = 'flex items-center justify-center rounded-lg shrink-0 p-0.5 [&_svg]:size-5'
  const variants: Record<Variant, string> = {
    default: 'bg-[#a3e635] text-slate-900',
    outline: 'border border-input bg-muted/50 text-foreground dark:text-primary-foreground',
    destructive: 'border border-destructive/40 bg-destructive/85 text-destructive-foreground',
    secondary: 'bg-secondary text-secondary-foreground dark:text-primary-foreground',
    ghost: 'bg-muted/50 text-muted-foreground dark:text-primary-foreground',
  }
  return cn(base, variants[props.variant], props.class)
})
</script>
