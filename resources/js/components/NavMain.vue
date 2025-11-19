<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuBadge,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

defineProps<{
    items: NavItem[];
    groupLabel?: string;
}>();

const page = usePage();

const hasChildren = (item: NavItem) => item.children && item.children.length > 0;
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel v-if="groupLabel">{{ groupLabel }}</SidebarGroupLabel>
        <SidebarMenu>
            <Collapsible
                v-for="item in items"
                :key="item.title"
                as-child
                :default-open="hasChildren(item) && item.children?.some(child => urlIsActive(child.href, page.url))"
                class="group/collapsible"
            >
                <SidebarMenuItem>
                    <!-- Item com submenus -->
                    <template v-if="hasChildren(item)">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton :tooltip="item.title">
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.label || item.title }}</span>
                                <SidebarMenuBadge v-if="item.badge">
                                    {{ item.badge }}
                                </SidebarMenuBadge>
                                <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="subItem in item.children" :key="subItem.title">
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="urlIsActive(subItem.href, page.url)"
                                    >
                                        <Link :href="subItem.href">
                                            <component :is="subItem.icon" v-if="subItem.icon" />
                                            <span>{{ subItem.label || subItem.title }}</span>
                                            <SidebarMenuBadge v-if="subItem.badge">
                                                {{ subItem.badge }}
                                            </SidebarMenuBadge>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </template>

                    <!-- Item sem submenus -->
                    <template v-else>
                        <SidebarMenuButton
                            as-child
                            :is-active="urlIsActive(item.href, page.url)"
                            :tooltip="item.title"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.label || item.title }}</span>
                                <SidebarMenuBadge v-if="item.badge">
                                    {{ item.badge }}
                                </SidebarMenuBadge>
                            </Link>
                        </SidebarMenuButton>
                    </template>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
