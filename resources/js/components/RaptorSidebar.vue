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
        groupIcon: typeof item.groupIcon === 'string'
            ? getIconComponent(item.groupIcon)
            : item.groupIcon,
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
    // Etapa 1: Montar blocos (diretos e grupos) com seus metadados de ordenação
    const blockMap = new Map<string, { key: string; blockOrder: number; isDirect: boolean; items: NavItem[] }>();

    navigationItems.value.forEach(item => {
        const blockKey = item.groupKey || item.group || 'direct';
        const blockOrder = item.blockOrder ?? item.order ?? 50;
        const isDirect = item.isDirect ?? !item.group;

        if (!blockMap.has(blockKey)) {
            blockMap.set(blockKey, {
                key: blockKey,
                blockOrder,
                isDirect,
                items: [],
            });
        }

        blockMap.get(blockKey)!.items.push(item);
    });

    // Etapa 2: Converter blocos para array e ordenar globalmente por blockOrder (com desempate por chave)
    const sortedBlocks = Array.from(blockMap.values())
        .sort((a, b) => {
            const orderDiff = a.blockOrder - b.blockOrder;
            if (orderDiff !== 0) {
                return orderDiff;
            }
            // Desempate estável: ordem alfabética da chave de bloco
            return a.key.localeCompare(b.key);
        });

    // Etapa 3: Processar cada bloco para renderização
    return sortedBlocks.map(block => {
        // Ordenar itens dentro do bloco por seu order individual
        block.items.sort((a, b) => (a.order || 50) - (b.order || 50));

        // Se é bloco direto (sem grupo), renderiza itens sem rótulo de seção
        if (block.isDirect) {
            return { name: 'direct', items: block.items, collapsible: false };
        }

        // Se é bloco de grupo, verificar se tem groupCollapsible
        const isCollapsible = block.items.some(item => item.groupCollapsible);

        if (isCollapsible) {
            const firstItem = block.items[0];
            const groupIcon = block.items.find(item => item.groupIcon)?.groupIcon || firstItem.icon;
            const groupName = firstItem.group || 'Geral';

            return {
                name: groupName,
                items: [{
                    title: groupName,
                    label: groupName,
                    href: firstItem.href,
                    icon: groupIcon,
                    children: block.items,
                }],
                collapsible: true,
            };
        }

        // Grupo não-colapsável: renderiza com rótulo de seção e itens
        return {
            name: block.key,
            items: block.items,
            collapsible: false,
        };
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
                    :group-label="(group.collapsible || group.name === 'direct') ? undefined : group.name"
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
