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
  <ResourceLayout v-bind="props" title="Dashboard">
    <template #content>
      <!-- Conteúdo específico da página -->
      <div>
        <h1 class="text-2xl font-bold">{{ resourceLabel }}</h1>
        <p class="text-muted-foreground">{{ message }}</p>
      </div>
    </template>
  </ResourceLayout>
</template>
