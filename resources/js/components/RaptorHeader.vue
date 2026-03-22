<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { useAppearance } from '@/composables/useAppearance';
import { SidebarTrigger } from '~/components/ui/sidebar';
import NotificationDropdown from '~/components/NotificationDropdown.vue';
import { ChevronRight, Sun, Moon } from 'lucide-vue-next';
import { cn } from '~/lib/utils';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { appearance, updateAppearance } = useAppearance();

// ── Dark mode toggle ─────────────────────────────────────────────────────────
const isSystemDark = ref(false);

onMounted(() => {
    isSystemDark.value = document.documentElement.classList.contains('dark');

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
        isSystemDark.value = event.matches;
    });
});

const isDark = computed(() => {
    if (appearance.value === 'system') {
        return isSystemDark.value;
    }

    return appearance.value === 'dark';
});

const toggleDark = () => {
    const nextAppearance = isDark.value ? 'light' : 'dark';

    updateAppearance(nextAppearance);
};
</script>

<template>
    <header
        class="flex h-12 shrink-0 items-center gap-2 border-b border-border bg-background/95 px-4 backdrop-blur-md transition-[height] group-has-data-[collapsible=icon]/sidebar-wrapper:h-10"
    >
        <!-- ── Esquerda: trigger + separador + breadcrumbs ──────────────────── -->
        <div class="flex flex-1 items-center gap-2 min-w-0">
            <SidebarTrigger class="-ml-1" />

            <!-- Separador vertical -->
            <span class="h-4 w-px shrink-0 bg-border/70" aria-hidden="true" />

            <!-- Breadcrumbs nativos -->
            <nav
                v-if="breadcrumbs && breadcrumbs.length > 0"
                aria-label="Breadcrumb"
                class="flex min-w-0 items-center gap-1 text-sm"
            >
                <template v-for="(crumb, index) in breadcrumbs" :key="crumb.href">
                    <!-- Separador -->
                    <ChevronRight
                        v-if="index > 0"
                        class="size-3 shrink-0 text-muted-foreground/50"
                    />

                    <!-- Link ou texto -->
                    <Link
                        v-if="crumb.href && index < breadcrumbs.length - 1"
                        :href="crumb.href"
                        :class="cn(
                            'truncate rounded px-1 py-0.5 transition-colors',
                            'text-muted-foreground hover:text-foreground',
                            'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                        )"
                    >
                        {{ crumb.title }}
                    </Link>
                    <span
                        v-else
                        :class="cn(
                            'truncate rounded px-1 py-0.5',
                            index === breadcrumbs.length - 1
                                ? 'font-medium text-foreground'
                                : 'text-muted-foreground',
                        )"
                        :aria-current="index === breadcrumbs.length - 1 ? 'page' : undefined"
                    >
                        {{ crumb.title }}
                    </span>
                </template>
            </nav>
        </div>

        <!-- ── Direita: ações ────────────────────────────────────────────────── -->
        <div class="flex items-center gap-0.5">
            <!-- Alternador dark/light -->
            <button
                type="button"
                :title="isDark ? 'Modo claro' : 'Modo escuro'"
                :aria-label="isDark ? 'Mudar para modo claro' : 'Mudar para modo escuro'"
                class="flex size-7 items-center justify-center rounded-md text-muted-foreground/70 transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                @click="toggleDark"
            >
                <Sun v-if="isDark" class="size-4" />
                <Moon v-else class="size-4" />
            </button>

            <!-- Notificações -->
            <NotificationDropdown />
        </div>
    </header>
</template>
