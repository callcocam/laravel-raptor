<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const spinnerVariants = cva(
    'animate-spin text-primary',
    {
        variants: {
            size: {
                xs: 'size-3',
                sm: 'size-4',
                default: 'size-5',
                lg: 'size-6',
                xl: 'size-8',
            },
        },
        defaultVariants: {
            size: 'default',
        },
    },
)

type SpinnerVariants = VariantProps<typeof spinnerVariants>

const props = defineProps<{
    size?: SpinnerVariants['size']
    class?: HTMLAttributes['class']
    label?: string
}>()
</script>

<template>
    <svg
        data-slot="spinner"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        :class="cn(spinnerVariants({ size }), props.class)"
        role="status"
        :aria-label="label ?? 'Carregando...'"
    >
        <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
        />
        <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
    </svg>
</template>
