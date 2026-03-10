<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()
</script>

<template>
    <legend :class="cn('px-1 text-sm font-semibold text-foreground', props.class)">
        <slot />
    </legend>
</template>
