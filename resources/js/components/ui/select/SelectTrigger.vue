<script setup lang="ts">
import type { HTMLAttributes, Ref } from 'vue'
import { inject } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import { cn } from '~/lib/utils'

const props = defineProps<{
    class?: HTMLAttributes['class']
    ariaInvalid?: boolean
}>()

const open     = inject<Ref<boolean>>('selectOpen')!
const disabled = inject<boolean | undefined>('selectDisabled')
const toggle   = inject<() => void>('selectToggle')!
</script>

<template>
    <button
        type="button"
        role="combobox"
        :aria-expanded="open"
        :aria-invalid="props.ariaInvalid"
        :disabled="disabled"
        :class="cn(
            'flex h-9 w-full items-center justify-between rounded-lg bg-[var(--color-input-surface)] border-[1.5px] border-transparent px-3 py-2',
            'text-sm placeholder:text-muted-foreground',
            'transition-[border-color,box-shadow,background-color]',
            'focus:outline-none focus:bg-card focus:border-[var(--color-input-focus)] focus:ring-2 focus:ring-[var(--color-input-focus)]/15',
            'disabled:cursor-not-allowed disabled:opacity-50',
            open && 'bg-card border-[var(--color-input-focus)] ring-2 ring-[var(--color-input-focus)]/15',
            props.ariaInvalid && 'border-destructive ring-2 ring-destructive/20',
            props.class,
        )"
        @click="toggle"
    >
        <slot />
        <ChevronDown
            class="size-4 shrink-0 opacity-50 transition-transform duration-200"
            :class="open ? 'rotate-180' : ''"
        />
    </button>
</template>
