<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
    as?: string | object
    class?: HTMLAttributes['class']
}>(), {
    as: 'div',
})
</script>

<template>
    <component
        :is="as"
        data-slot="sidebar-group-label"
        data-sidebar="group-label"
        :class="cn(
            'text-sidebar-foreground/35 ring-sidebar-ring flex h-7 shrink-0 items-center rounded-md px-3 text-[10px] font-semibold uppercase tracking-widest outline-hidden transition-[margin,opacity] duration-200 ease-linear select-none focus-visible:ring-2 [&>svg]:size-3.5 [&>svg]:shrink-0',
            'group-data-[collapsible=icon]:-mt-8 group-data-[collapsible=icon]:opacity-0',
            props.class,
        )"
    >
        <slot />
    </component>
</template>
