<script setup lang="ts">
import ResourceLayout from './../../../layouts/ResourceLayout.vue'
import type { BackendBreadcrumb } from '@/composables/useBreadcrumbs'

interface Props {
  message?: string
  resourceLabel?: string
  breadcrumbs?: BackendBreadcrumb[]
  model?: any
  infolist?: any[]
}

const props = defineProps<Props>()

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  breadcrumbs: props.breadcrumbs,
}
</script>

<template>
  <ResourceLayout v-bind="layoutProps" title="Visualizar">
    <template #content>
      <div class="space-y-4">
        <div v-if="resourceLabel || message">
          <h1 v-if="resourceLabel" class="text-2xl font-bold">{{ resourceLabel }}</h1>
          <p v-if="message" class="text-muted-foreground mt-1">{{ message }}</p>
        </div>

        <!-- Infolist will be rendered here -->
        <pre v-if="model" class="p-4 bg-muted rounded-md text-xs">{{ model }}</pre>
      </div>
    </template>
  </ResourceLayout>
</template>
