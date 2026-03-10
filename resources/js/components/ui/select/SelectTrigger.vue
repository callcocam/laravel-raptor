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
            'flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2',
            'text-sm ring-offset-background placeholder:text-muted-foreground',
            'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
            'disabled:cursor-not-allowed disabled:opacity-50',
            open && 'ring-2 ring-ring ring-offset-2',
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
