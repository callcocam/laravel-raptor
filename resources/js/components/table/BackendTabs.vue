<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import RaptorIcon from '~/components/icon.vue';

interface BackendTab {
    key: string;
    name: string;
    href: string;
    icon?: string;
    badge?: number | null;
    active?: boolean;
}

const props = defineProps<{
    tabs: BackendTab[];
}>();

const hasExplicitActive = computed(() => props.tabs.some(t => t.active === true));

const isActive = (tab: BackendTab): boolean => {
    if (hasExplicitActive.value) {
        return tab.active === true;
    }
    return window.location.pathname === tab.href || window.location.pathname.startsWith(tab.href + '/');
};
</script>

<template>
    <nav
        class="inline-flex items-center rounded-xl bg-muted"
        aria-label="Tabs"
    >
        <Link
            v-for="tab in props.tabs"
            :key="tab.key"
            :href="tab.href"
            :class="[
                isActive(tab)
                    ? 'bg-card text-primary shadow-sm'
                    : 'text-muted-foreground hover:text-foreground',
                'group inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors'
            ]"
            :aria-current="isActive(tab) ? 'page' : undefined"
        >
            <RaptorIcon
                v-if="tab.icon"
                :is="tab.icon"
                :class="isActive(tab) ? 'h-4 w-4 text-primary transition-colors' : 'h-4 w-4 text-muted-foreground transition-colors group-hover:text-foreground'"
            />
            <span>{{ tab.name }}</span>
            <span
                v-if="tab.badge != null"
                class="rounded-full bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary"
            >{{ tab.badge }}</span>
        </Link>
    </nav>
</template>
