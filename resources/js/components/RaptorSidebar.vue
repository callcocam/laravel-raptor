<script setup lang="ts">
import { computed } from 'vue';
import NavFooter from './NavFooter.vue';
import NavMain from './NavMain.vue';
import NavUser from './NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from './ui/sidebar';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import * as LucideIcons from 'lucide-vue-next';

interface Props {
    dashboardUrl?: string;
    dashboardSlot?: boolean;
    footerItems?: NavItem[];
}

const page = usePage();

const props = withDefaults(defineProps<Props>(), {
    dashboardUrl: '/',
    dashboardSlot: false,
    footerItems: () => [],
});

const getIconComponent = (iconName: string) => {
    return (LucideIcons as any)[iconName] || LucideIcons.Circle;
};

const navigationItems = computed(() => {
    const navData = (page.props.raptor?.navigation as NavItem[]) || [];

    return navData.map(item => ({
        ...item,
        icon: typeof item.icon === 'string' ? getIconComponent(item.icon) : item.icon,
        groupIcon: typeof (item as any).groupIcon === 'string'
            ? getIconComponent((item as any).groupIcon)
            : (item as any).groupIcon,
    }));
});

const footerNavItems = computed(() => {
    const raptorFooter = (page.props.raptor?.navigationFooter as NavItem[]) || [];
    const allFooterItems = [...props.footerItems, ...raptorFooter];

    return allFooterItems.map(item => ({
        ...item,
        icon: typeof item.icon === 'string' ? getIconComponent(item.icon) : item.icon,
    }));
});

const groupedNavigation = computed(() => {
    const groups = new Map<string, NavItem[]>();

    navigationItems.value.forEach(item => {
        const groupName = item.group || 'Geral';
        if (!groups.has(groupName)) {
            groups.set(groupName, []);
        }
        groups.get(groupName)!.push(item);
    });

    groups.forEach(items => {
        items.sort((a, b) => (a.order || 50) - (b.order || 50));
    });

    return Array.from(groups.entries()).map(([name, items]) => {
        const isCollapsible = items.some(item => item.groupCollapsible);

        if (isCollapsible) {
            const firstItem = items[0];
            const groupIcon = items.find(item => (item as any).groupIcon)?.groupIcon || firstItem.icon;

            return {
                name,
                items: [{
                    title: name,
                    label: name,
                    href: firstItem.href,
                    icon: groupIcon,
                    children: items,
                }],
                collapsible: true,
            };
        }

        return { name, items, collapsible: false };
    });
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">

        <!-- ── Header ──────────────────────────────────────────────── -->
        <SidebarHeader class="border-b border-sidebar-border px-3 py-2.5 group-data-[collapsible=icon]:px-1.5">
            <div class="flex h-9 items-center group-data-[collapsible=icon]:justify-center">
                <slot name="logo">
                    <!-- Fallback: icon + text (text hidden in collapsed mode) -->
                    <a :href="dashboardUrl" class="flex items-center gap-2.5">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                        >
                            <LucideIcons.Zap class="size-4" />
                        </span>
                        <span class="truncate text-sm font-bold text-sidebar-foreground group-data-[collapsible=icon]:hidden">
                            Laravel Raptor
                        </span>
                    </a>
                </slot>
            </div>
        </SidebarHeader>

        <!-- ── Navigation ─────────────────────────────────────────── -->
        <SidebarContent class="gap-0 py-1">
            <template v-for="(group, idx) in groupedNavigation" :key="group.name">
                <SidebarSeparator
                    v-if="idx > 0"
                    class="mx-3 my-0.5 bg-sidebar-border/40"
                />
                <NavMain
                    :group-label="group.collapsible ? undefined : group.name"
                    :items="group.items"
                />
            </template>
        </SidebarContent>

        <!-- ── Footer ─────────────────────────────────────────────── -->
        <SidebarFooter class="border-t border-sidebar-border px-2 py-2">
            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" class="pb-1" />
            <NavUser />
        </SidebarFooter>

    </Sidebar>
    <slot />
</template>
