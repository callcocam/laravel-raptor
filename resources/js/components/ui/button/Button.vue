<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { type ButtonVariants, buttonVariants } from '.'

interface Props {
    as?: string | object
    variant?: ButtonVariants['variant']
    size?: ButtonVariants['size']
    class?: HTMLAttributes['class']
    disabled?: boolean
    type?: 'button' | 'submit' | 'reset'
}

const props = withDefaults(defineProps<Props>(), {
    as: 'button',
    type: 'button',
})
</script>

<template>
    <component
        :is="as"
        data-slot="button"
        :data-variant="variant ?? 'default'"
        :type="as === 'button' ? type : undefined"
        :disabled="as === 'button' ? disabled : undefined"
        :class="cn(buttonVariants({ variant, size }), props.class)"
    >
        <slot />
    </component>
</template>
