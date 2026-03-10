<script setup lang="ts">
import type { HTMLAttributes, Ref } from 'vue'
import { inject } from 'vue'
import { Check } from 'lucide-vue-next'
import { cn } from '~/lib/utils'

const props = defineProps<{
    value: string
    label?: string
    disabled?: boolean
    class?: HTMLAttributes['class']
}>()

const modelValue   = inject<Ref<string | number | null | undefined>>('selectModelValue')!
const selectValue  = inject<(v: string) => void>('selectValue')!
const registerItem = inject<(item: { value: string; label: string }) => void>('selectRegisterItem')!

// Registra de forma síncrona (antes do render) usando prop label ou fallback ao value.
// Isso garante que SelectValue já encontre o label correto na primeira renderização.
registerItem({ value: props.value, label: props.label ?? props.value })
</script>

<template>
    <div
        role="option"
        :aria-selected="modelValue === props.value"
        :aria-disabled="props.disabled"
        :class="cn(
            'relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none',
            'transition-colors hover:bg-accent hover:text-accent-foreground',
            modelValue === props.value && 'bg-accent/50 font-medium',
            props.disabled && 'pointer-events-none opacity-50',
            props.class,
        )"
        @click="!props.disabled && selectValue(props.value)"
    >
        <span class="absolute left-2 flex size-3.5 items-center justify-center">
            <Check v-if="modelValue === props.value" class="size-4" />
        </span>
        <slot />
    </div>
</template>
