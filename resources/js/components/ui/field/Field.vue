<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'

const props = withDefaults(defineProps<{
    orientation?: 'vertical' | 'horizontal'
    class?: HTMLAttributes['class']
}>(), {
    orientation: 'vertical',
})
</script>

<template>
    <div
        :class="cn(
            'flex gap-1',
            props.orientation === 'horizontal' ? 'flex-row items-start' : 'flex-col',
            props.class,
        )"
        v-bind="$attrs"
    >
        <slot />
    </div>
</template>
