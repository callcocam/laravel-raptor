<script setup lang="ts">
/**
 * Dialog — componente raiz do modal nativo.
 * Fornece o estado open/close via provide para DialogContent.
 */
import { provide, watch } from 'vue'
import { useEventListener } from '@vueuse/core'

const open = defineModel<boolean>('open', { default: false })

provide('dialogOpen', open)
provide('dialogClose', () => { open.value = false })

// Fecha com Escape; listener ativo apenas quando aberto
useEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && open.value) {
        open.value = false
    }
})

// Trava o scroll do body quando o dialog está aberto
watch(open, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : ''
}, { immediate: true })
</script>

<template>
    <slot />
</template>
