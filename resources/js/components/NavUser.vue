<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from './ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';

const page = usePage();
const user = page.props.auth.user;
const { isMobile, state } = useSidebar();
const { getInitials } = useInitials();
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="rounded-lg hover:bg-sidebar-accent/80 data-[state=open]:bg-sidebar-accent"
                        data-test="sidebar-menu-button"
                    >
                        <!-- Avatar -->
                        <span
                            class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-md border border-sidebar-border bg-sidebar-accent text-xs font-bold text-sidebar-foreground/70 transition-colors"
                        >
                            <img
                                v-if="user?.avatar"
                                :src="user.avatar"
                                :alt="user?.name"
                                class="size-full object-cover"
                            />
                            <span v-else>{{ getInitials(user?.name ?? '') }}</span>
                        </span>

                        <!-- Name + role/email -->
                        <span class="grid flex-1 text-left leading-tight">
                            <span class="truncate text-sm font-semibold text-sidebar-foreground">
                                {{ user?.name }}
                            </span>
                            <span class="truncate text-[10px] text-sidebar-foreground/40">
                                {{ user?.email }}
                            </span>
                        </span>

                        <ChevronsUpDown class="ml-auto size-3.5 shrink-0 text-sidebar-foreground/35" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    class="min-w-56 rounded-lg"
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'"
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
