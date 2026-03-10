<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Skeleton } from '../skeleton'

const props = withDefaults(defineProps<{
    showIcon?: boolean
    class?: HTMLAttributes['class']
}>(), {
    showIcon: false,
})
</script>

<template>
    <div
        data-slot="sidebar-menu-skeleton"
        data-sidebar="menu-skeleton"
        :class="cn('flex h-8 items-center gap-2 rounded-md px-2', props.class)"
    >
        <Skeleton
            v-if="showIcon"
            class="size-4 shrink-0 rounded-md"
        />
        <Skeleton
            class="h-4 flex-1 max-w-[--skeleton-width]"
            :style="{ '--skeleton-width': `${Math.floor(Math.random() * 40) + 50}%` }"
        />
    </div>
</template>
