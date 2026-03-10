<script setup lang="ts">
import type { HTMLAttributes, LabelHTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'

const props = defineProps<{
    class?: HTMLAttributes['class']
    for?: LabelHTMLAttributes['for']
    required?: boolean
}>()
</script>

<template>
    <label
        data-slot="label"
        :for="props.for"
        :class="cn(
            'inline-flex items-center gap-1 text-sm font-medium leading-none',
            'text-foreground dark:text-foreground',
            'peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
            props.class,
        )"
    >
        <slot />
        <span v-if="required" class="text-destructive" aria-hidden="true">*</span>
    </label>
</template>
