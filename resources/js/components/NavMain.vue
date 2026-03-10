<script setup lang="ts">
import { ref, onMounted } from 'vue';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuBadge,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubItem,
    useSidebar,
} from './ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    items: NavItem[];
    groupLabel?: string;
}>();

const page = usePage();
const { state, isMobile } = useSidebar();

const hasChildren = (item: NavItem) => !!(item.children && item.children.length > 0);

// ── Native collapsible (no reka-ui) ─────────────────────────────────────────
// Replaces Collapsible / CollapsibleTrigger / CollapsibleContent from reka-ui.
const openGroups = ref<Record<string, boolean>>({});
const isOpen = (key: string) => !!openGroups.value[key];
const toggleGroup = (key: string) => {
    openGroups.value[key] = !openGroups.value[key];
};

// Open groups that have an active child on mount
onMounted(() => {
    props.items.forEach(item => {
        if (hasChildren(item) && item.children?.some(c => urlIsActive(c.href, page.url))) {
            openGroups.value[item.title] = true;
        }
    });
});

// ── Collapsed flyout ─────────────────────────────────────────────────────────
// Teleport-based flyout panel shown when sidebar is in icon-only mode.
const activeFlyout = ref<string | null>(null);
const flyoutPos = ref({ top: 0, left: 0 });
let closeTimer: ReturnType<typeof setTimeout> | null = null;

const anchorRefs = ref<Record<string, HTMLElement>>({});
const setAnchorRef = (key: string, el: HTMLElement | null) => {
    if (el) anchorRefs.value[key] = el;
};

const openFlyout = (key: string) => {
    if (state.value !== 'collapsed' || isMobile.value) return;
    if (closeTimer) clearTimeout(closeTimer);
    const el = anchorRefs.value[key];
    if (el) {
        const rect = el.getBoundingClientRect();
        flyoutPos.value = {
            top: Math.min(Math.max(8, rect.top), window.innerHeight - 250),
            left: rect.right + 6,
        };
    }
    activeFlyout.value = key;
};

const scheduleFlyoutClose = () => {
    closeTimer = setTimeout(() => { activeFlyout.value = null; }, 150);
};

const cancelFlyoutClose = () => {
    if (closeTimer) clearTimeout(closeTimer);
};
</script>

<template>
    <SidebarGroup class="px-2 py-0.5">
        <!-- Group label -->
        <p
            v-if="groupLabel"
            class="mb-0.5 mt-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.1em] text-sidebar-foreground/35 select-none group-data-[collapsible=icon]:hidden"
        >
            {{ groupLabel }}
        </p>

        <SidebarMenu class="gap-0.5">
            <SidebarMenuItem
                v-for="item in items"
                :key="item.title"
            >
                <!-- ── Parent item (has children) ─────────────────────── -->
                <template v-if="hasChildren(item)">
                    <!--
                        Native <div> as flyout anchor — avoids any component
                        boundary interference when reading getBoundingClientRect().
                    -->
                    <div
                        :ref="(el) => setAnchorRef(item.title, el as HTMLElement)"
                        @mouseenter="openFlyout(item.title)"
                        @mouseleave="scheduleFlyoutClose()"
                    >
                        <!-- Native button replaces CollapsibleTrigger + SidebarMenuButton as-child -->
                        <SidebarMenuButton
                            :is-active="item.children?.some(c => urlIsActive(c.href, page.url))"
                            :tooltip="item.title"
                            @click="toggleGroup(item.title)"
                        >
                            <component :is="item.icon" v-if="item.icon" class="size-4 shrink-0" />
                            <span class="flex-1 truncate group-data-[collapsible=icon]:hidden">
                                {{ item.label || item.title }}
                            </span>
                            <SidebarMenuBadge
                                v-if="item.badge"
                                class="mr-1 rounded-full bg-sidebar-primary/12 px-1.5 text-[10px] font-semibold text-sidebar-primary group-data-[collapsible=icon]:hidden"
                            >
                                {{ item.badge }}
                            </SidebarMenuBadge>
                            <ChevronRight
                                class="ml-auto size-3.5 shrink-0 text-sidebar-foreground/25 transition-transform duration-200 group-data-[collapsible=icon]:hidden"
                                :class="{ 'rotate-90 text-sidebar-primary/60': isOpen(item.title) }"
                            />
                        </SidebarMenuButton>
                    </div>

                    <!-- Sub-items — native v-show + CSS transition (no reka-ui) -->
                    <Transition name="submenu">
                        <SidebarMenuSub
                            v-show="isOpen(item.title)"
                            class="mx-0 my-0.5 ml-6 border-l border-sidebar-border/40 pl-0 pr-0 group-data-[collapsible=icon]:hidden"
                        >
                            <SidebarMenuSubItem
                                v-for="subItem in item.children"
                                :key="subItem.title"
                            >
                                <Link
                                    :href="subItem.href"
                                    class="flex h-7 w-full items-center gap-2 rounded-sm px-2.5 text-[0.8125rem] font-medium transition-colors"
                                    :class="[
                                        urlIsActive(subItem.href, page.url)
                                            ? 'text-sidebar-primary'
                                            : 'text-sidebar-foreground/50 hover:text-sidebar-foreground hover:bg-sidebar-accent/60'
                                    ]"
                                >
                                    <component :is="subItem.icon" v-if="subItem.icon" class="size-3.5 shrink-0" />
                                    <span class="truncate">{{ subItem.label || subItem.title }}</span>
                                    <span
                                        v-if="subItem.badge"
                                        class="ml-auto rounded-full bg-sidebar-primary/12 px-1.5 text-[9px] font-semibold text-sidebar-primary"
                                    >
                                        {{ subItem.badge }}
                                    </span>
                                </Link>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </Transition>
                </template>

                <!-- ── Leaf item (no children) ────────────────────────── -->
                <!--
                    Uses SidebarMenuButton directly (renders a <button>) + Inertia router.visit.
                    Avoids `as-child` cloneVNode on Inertia <Link> which caused layout issues
                    (icon and text not in the same flex row).
                -->
                <template v-else>
                    <SidebarMenuButton
                        :is-active="urlIsActive(item.href, page.url)"
                        :tooltip="item.title"
                        @click="router.visit(item.href)"
                    >
                        <component :is="item.icon" v-if="item.icon" class="size-4 shrink-0" />
                        <span class="flex-1 truncate group-data-[collapsible=icon]:hidden">
                            {{ item.label || item.title }}
                        </span>
                        <span
                            v-if="item.badge"
                            class="ml-auto rounded-full bg-sidebar-primary/12 px-1.5 text-[10px] font-semibold text-sidebar-primary group-data-[collapsible=icon]:hidden"
                        >
                            {{ item.badge }}
                        </span>
                    </SidebarMenuButton>
                </template>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <!-- ── Collapsed flyout (Teleport escapes overflow:hidden) ───────────── -->
    <Teleport to="body">
        <Transition name="flyout">
            <div
                v-if="activeFlyout && state === 'collapsed' && !isMobile"
                class="fixed z-[60] min-w-44 overflow-hidden rounded-lg border border-border bg-sidebar py-1.5 shadow-lg"
                :style="{ top: `${flyoutPos.top}px`, left: `${flyoutPos.left}px` }"
                @mouseenter="cancelFlyoutClose()"
                @mouseleave="scheduleFlyoutClose()"
            >
                <template v-for="item in items" :key="item.title">
                    <template v-if="item.title === activeFlyout && hasChildren(item)">
                        <p class="px-3 pb-1.5 pt-0.5 text-[10px] font-semibold uppercase tracking-wider text-sidebar-foreground/40">
                            {{ item.label || item.title }}
                        </p>
                        <Link
                            v-for="child in item.children"
                            :key="child.title"
                            :href="child.href"
                            class="flex h-8 items-center gap-2.5 px-3 text-sm font-medium transition-colors"
                            :class="[
                                urlIsActive(child.href, page.url)
                                    ? 'bg-sidebar-primary/10 text-sidebar-primary'
                                    : 'text-sidebar-foreground/70 hover:text-sidebar-foreground hover:bg-sidebar-accent/70'
                            ]"
                            @click="activeFlyout = null"
                        >
                            <component :is="child.icon" v-if="child.icon" class="size-3.5 shrink-0" />
                            <span class="truncate">{{ child.label || child.title }}</span>
                            <span
                                v-if="child.badge"
                                class="ml-auto rounded-full bg-sidebar-primary/12 px-1.5 text-[9px] font-semibold text-sidebar-primary"
                            >
                                {{ child.badge }}
                            </span>
                        </Link>
                    </template>
                </template>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Native submenu expand/collapse */
.submenu-enter-active,
.submenu-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.submenu-enter-from,
.submenu-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

/* Collapsed flyout */
.flyout-enter-active,
.flyout-leave-active {
    transition: opacity 0.12s ease, transform 0.12s ease;
}
.flyout-enter-from,
.flyout-leave-to {
    opacity: 0;
    transform: translateX(-6px);
}
</style>
