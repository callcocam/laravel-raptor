<script setup lang="ts">
import ResourceLayout from '~/layouts/ResourceLayout.vue';
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
  <ResourceLayout v-bind="props" title="Comentários">
    <template #content>
      <div>
        <h1 class="text-2xl font-bold">{{ resourcePluralLabel || 'Comentários' }}</h1>
        <p class="text-muted-foreground mt-2">{{ message || 'Gerenciar comentários dos posts' }}</p>
      </div>
    </template>
  </ResourceLayout>
</template>
