<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { useBreadcrumbs, type BackendBreadcrumb } from '@/composables/useBreadcrumbs';
import { dashboard } from '@/routes';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

interface Props {
    message: string;
    resourceName: string;
    resourcePluralName: string;
    resourceLabel: string;
    resourcePluralLabel: string;
    breadcrumbs?: BackendBreadcrumb[];
}

const props = defineProps<Props>();

// Mapeia breadcrumbs do backend para o formato do frontend
const breadcrumbs = useBreadcrumbs(
    () => props.breadcrumbs,
    [{ title: 'Dashboard', href: dashboard().url }]
);

onMounted(() => {
    document.title = 'Dashboard';
});
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
        <div
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
        <div
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
      </div>
      <div
        class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
      >
        <PlaceholderPattern />
      </div>
    </div>
  </AppLayout>
</template>
