<script setup lang="ts">
import { cn } from '@/lib/utils'
import { useEventListener, useMediaQuery, useVModel } from '@vueuse/core'
import { computed, type HTMLAttributes, type Ref, ref } from 'vue'
import { provideSidebarContext, SIDEBAR_COOKIE_MAX_AGE, SIDEBAR_COOKIE_NAME, SIDEBAR_KEYBOARD_SHORTCUT, SIDEBAR_WIDTH, SIDEBAR_WIDTH_ICON } from './utils'

const props = withDefaults(defineProps<{
    defaultOpen?: boolean
    open?: boolean
    class?: HTMLAttributes['class']
    /**
     * Quando true, o wrapper ocupa exatamente 100svh sem overflow,
     * permitindo que conteúdos internos (Kanban, tabelas, etc.)
     * se limitem à altura da tela com scroll independente por coluna/área.
     */
    fullHeight?: boolean
}>(), {
    defaultOpen: true,
    open: undefined,
    fullHeight: false,
})

const emits = defineEmits<{
    'update:open': [open: boolean]
}>()

const isMobile = useMediaQuery('(max-width: 768px)')
const openMobile = ref(false)

const open = useVModel(props, 'open', emits, {
    defaultValue: props.defaultOpen ?? false,
    passive: (props.open === undefined) as false,
}) as Ref<boolean>

function setOpen(value: boolean) {
    open.value = value
    document.cookie = `${SIDEBAR_COOKIE_NAME}=${open.value}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`
}

function setOpenMobile(value: boolean) {
    openMobile.value = value
}

function toggleSidebar() {
    return isMobile.value ? setOpenMobile(!openMobile.value) : setOpen(!open.value)
}

useEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key === SIDEBAR_KEYBOARD_SHORTCUT && (event.metaKey || event.ctrlKey)) {
        event.preventDefault()
        toggleSidebar()
    }
})

const state = computed(() => open.value ? 'expanded' : 'collapsed')

provideSidebarContext({
    state,
    open,
    setOpen,
    isMobile,
    openMobile,
    setOpenMobile,
    toggleSidebar,
})
</script>

<template>
    <div
        data-slot="sidebar-wrapper"
        :style="{
            '--sidebar-width': SIDEBAR_WIDTH,
            '--sidebar-width-icon': SIDEBAR_WIDTH_ICON,
        }"
        :class="cn(
            'group/sidebar-wrapper has-data-[variant=inset]:bg-sidebar flex w-full',
            props.fullHeight ? 'h-svh overflow-hidden' : 'min-h-svh',
            props.class,
        )"
        v-bind="$attrs"
    >
        <slot />
    </div>
</template>
