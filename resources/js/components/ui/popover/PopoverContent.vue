<script setup lang="ts">
import type { HTMLAttributes, Ref } from 'vue'
import { inject } from 'vue'
import { useEventListener } from '@vueuse/core'
import { cn } from '~/lib/utils'

const props = withDefaults(defineProps<{
    align?: 'start' | 'center' | 'end'
    class?: HTMLAttributes['class']
}>(), {
    align: 'start',
})

const open = inject<Ref<boolean>>('popoverOpen')!
const close = inject<() => void>('popoverClose')!

useEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && open.value) close()
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-150 ease-out"
            leave-active-class="transition-all duration-100 ease-in"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                :class="cn(
                    'fixed z-50 min-w-[8rem] rounded-md border bg-popover p-1 text-popover-foreground shadow-md outline-none',
                    props.class,
                )"
                @click.stop
            >
                <slot />
            </div>
        </Transition>
    </Teleport>
</template>
