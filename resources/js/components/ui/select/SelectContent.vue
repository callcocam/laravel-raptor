<script setup lang="ts">
import type { HTMLAttributes, Ref } from 'vue'
import { inject } from 'vue'
import { cn } from '~/lib/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()

const open = inject<Ref<boolean>>('selectOpen')!
</script>

<template>
    <!-- v-show mantém os SelectItems montados para que registrem seus labels antes do render -->
    <Transition
        enter-active-class="transition-all duration-150 ease-out"
        leave-active-class="transition-all duration-100 ease-in"
        enter-from-class="opacity-0 scale-95 -translate-y-1"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 -translate-y-1"
    >
        <div
            v-show="open"
            :class="cn(
                'absolute top-full z-50 mt-1 max-h-60 w-full min-w-[8rem] overflow-y-auto',
                'rounded-md border bg-popover text-popover-foreground shadow-md',
                props.class,
            )"
        >
            <div class="p-1">
                <slot />
            </div>
        </div>
    </Transition>
</template>
