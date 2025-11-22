<script setup lang="ts">
import ResourceLayout from '@/../../packages/callcocam/laravel-raptor/resources/js/layouts/ResourceLayout.vue';
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
       dad
    </template>
  </ResourceLayout>
</template>
