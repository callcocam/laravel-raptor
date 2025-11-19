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
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import * as LucideIcons from 'lucide-vue-next';

interface Props {
    dashboardUrl?: string;
    dashboardSlot?: boolean;
    footerItems?: NavItem[];
}

const props = withDefaults(defineProps<Props>(), {
    dashboardUrl: '/',
    dashboardSlot: false,
    footerItems: () => [
        {
            title: 'Github Repo',
            href: 'https://github.com/laravel/vue-starter-kit',
            icon: 'Folder' as any,
        }, 
    ],
});

const page = usePage();

const getIconComponent = (iconName: string) => {
    return (LucideIcons as any)[iconName] || LucideIcons.Circle;
};

const navigationItems = computed(() => {
    const navData = (page.props.navigation as NavItem[]) || [];

    return navData.map(item => ({
        ...item,
        icon: typeof item.icon === 'string'
            ? getIconComponent(item.icon)
            : item.icon,
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

    groups.forEach((items) => {
        items.sort((a, b) => (a.order || 50) - (b.order || 50));
    });

    return Array.from(groups.entries()).map(([name, items]) => {
        // Verifica se algum item do grupo quer que seja collapsible
        const isCollapsible = items.some(item => item.groupCollapsible);

        if (isCollapsible) {
            // Renderiza como menu collapsible: cria item principal com TODOS como children
            const firstItem = items[0];
            return {
                name,
                items: [{
                    title: name,
                    label: name,
                    href: firstItem.href, // usa href do primeiro como fallback
                    icon: firstItem.icon,
                    children: items, // TODOS os items viram children
                }],
                collapsible: true,
            };
        }

        // Renderiza como divisor visual (comportamento atual)
        return {
            name,
            items,
            collapsible: false,
        };
    });
});

const footerNavItems = computed(() => {
    return props.footerItems.map(item => ({
        ...item,
        icon: typeof item.icon === 'string'
            ? getIconComponent(item.icon)
            : item.icon,
    }));
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <slot name="logo">
                            <a :href="dashboardUrl">
                                <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                    <LucideIcons.Zap class="size-4" />
                                </div>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">Laravel Raptor</span>
                                    <span class="truncate text-xs">Multi-tenant</span>
                                </div>
                            </a>
                        </slot>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="group in groupedNavigation"
                :key="group.name"
                :group-label="group.collapsible ? undefined : group.name"
                :items="group.items"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
