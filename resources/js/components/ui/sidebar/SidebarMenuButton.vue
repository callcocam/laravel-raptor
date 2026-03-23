<script setup lang="ts">
import { type Component, computed, ref } from 'vue'
import SidebarMenuButtonChild, { type SidebarMenuButtonProps } from './SidebarMenuButtonChild.vue'
import { useSidebar } from './utils'

defineOptions({
    inheritAttrs: false,
})

const props = withDefaults(defineProps<SidebarMenuButtonProps & {
    tooltip?: string | Component
}>(), {
    as: 'button',
    asChild: false,
    variant: 'default',
    size: 'default',
})

const { isMobile, state } = useSidebar()

const delegatedProps = computed(() => {
    const delegated = { ...props }
    delete (delegated as { tooltip?: string | Component }).tooltip
    return delegated
})

const showTooltip = computed(() => !!props.tooltip && state.value === 'collapsed' && !isMobile.value)

const tooltipVisible = ref(false)
</script>

<template>
    <div class="relative">
        <SidebarMenuButtonChild
            v-bind="{ ...delegatedProps, ...$attrs }"
            @mouseenter="tooltipVisible = true"
            @mouseleave="tooltipVisible = false"
            @focus="tooltipVisible = true"
            @blur="tooltipVisible = false"
        >
            <slot />
        </SidebarMenuButtonChild>

        <!-- Native tooltip — shown only when sidebar is collapsed on desktop -->
        <Transition name="tooltip-fade">
            <div
                v-if="showTooltip && tooltipVisible"
                role="tooltip"
                class="pointer-events-none absolute top-1/2 left-full z-50 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2.5 py-1 text-xs text-popover-foreground shadow-md dark:border-border dark:bg-popover dark:text-popover-foreground"
            >
                <template v-if="typeof tooltip === 'string'">{{ tooltip }}</template>
                <component :is="tooltip" v-else />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.tooltip-fade-enter-active,
.tooltip-fade-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.tooltip-fade-enter-from,
.tooltip-fade-leave-to {
    opacity: 0;
    transform: translateY(-50%) translateX(-4px);
}
</style>
