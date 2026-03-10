<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
    orientation?: 'horizontal' | 'vertical'
    decorative?: boolean
    class?: HTMLAttributes['class']
}>(), {
    orientation: 'horizontal',
    decorative: true,
})
</script>

<template>
    <div
        data-slot="separator"
        :role="decorative ? 'none' : 'separator'"
        :aria-orientation="!decorative ? orientation : undefined"
        :class="cn(
            'shrink-0 bg-border',
            orientation === 'horizontal' ? 'h-px w-full' : 'h-full w-px',
            props.class,
        )"
    />
</template>
