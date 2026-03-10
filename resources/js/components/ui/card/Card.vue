<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'

const props = defineProps<{
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <div
        data-slot="card"
        :class="cn(
            'rounded-xl border border-border bg-card text-card-foreground shadow-sm',
            'dark:border-border dark:bg-card dark:text-card-foreground dark:shadow-none',
            props.class,
        )"
    >
        <slot />
    </div>
</template>
