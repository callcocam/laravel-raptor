<script setup lang="ts">
import ResourceLayout from '~/layouts/ResourceLayout.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { type BackendBreadcrumb } from '@/composables/useBreadcrumbs';

interface Props {
    message?: string;
    resourceName?: string;
    resourcePluralName?: string;
    resourceLabel?: string;
    resourcePluralLabel?: string;
    maxWidth?: string;
    breadcrumbs?: BackendBreadcrumb[];
}

const props = defineProps<Props>();
</script>

<template>
  <ResourceLayout v-bind="props" title="Dashboard">
    <template #content>
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
    </template>
  </ResourceLayout>
</template>
