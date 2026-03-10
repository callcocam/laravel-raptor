<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const badgeVariants = cva(
    'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none',
    {
        variants: {
            variant: {
                default:
                    'border-transparent bg-primary text-primary-foreground',
                secondary:
                    'border-transparent bg-secondary text-secondary-foreground dark:bg-secondary/80',
                destructive:
                    'border-transparent bg-destructive text-white dark:bg-destructive/80',
                outline:
                    'border-border bg-transparent text-foreground dark:border-border dark:text-foreground',
                success:
                    'border-transparent bg-green-500/15 text-green-700 dark:bg-green-500/20 dark:text-green-400',
                warning:
                    'border-transparent bg-amber-500/15 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400',
                info:
                    'border-transparent bg-blue-500/15 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
)

type BadgeVariants = VariantProps<typeof badgeVariants>

const props = defineProps<{
    variant?: BadgeVariants['variant']
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <span
        data-slot="badge"
        :data-variant="variant ?? 'default'"
        :class="cn(badgeVariants({ variant }), props.class)"
    >
        <slot />
    </span>
</template>
