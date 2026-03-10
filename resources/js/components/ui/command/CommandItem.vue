<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'

const props = defineProps<{
    value?: string
    disabled?: boolean
    class?: HTMLAttributes['class']
}>()

const emit = defineEmits<{ (e: 'select', value: string | undefined): void }>()
</script>

<template>
    <div
        role="option"
        :aria-selected="false"
        :aria-disabled="props.disabled"
        :class="cn(
            'relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none',
            'transition-colors hover:bg-accent hover:text-accent-foreground',
            'data-[disabled=true]:pointer-events-none data-[disabled=true]:opacity-50',
            props.class,
        )"
        :data-disabled="props.disabled"
        @click="!props.disabled && emit('select', props.value)"
    >
        <slot />
    </div>
</template>
