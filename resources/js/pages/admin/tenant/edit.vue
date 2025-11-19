<script setup lang="ts">
import ResourceLayout from './../../../layouts/ResourceLayout.vue'
import type { BackendBreadcrumb } from '@/composables/useBreadcrumbs'

interface Props {
  message?: string
  resourceLabel?: string
  breadcrumbs?: BackendBreadcrumb[]
  model?: any
  form?: any
}

const props = defineProps<Props>()

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  breadcrumbs: props.breadcrumbs,
}
</script>

<template>
  <ResourceLayout v-bind="layoutProps" title="Editar">
    <template #content>
      <div class="space-y-4">
        <div v-if="resourceLabel || message">
          <h1 v-if="resourceLabel" class="text-2xl font-bold">Editar {{ resourceLabel }}</h1>
          <p v-if="message" class="text-muted-foreground mt-1">{{ message }}</p>
        </div>

        <!-- Form will be rendered here -->
        <div class="text-sm text-muted-foreground">
          Formulário em desenvolvimento...
        </div>

        <pre v-if="model" class="mt-4 p-4 bg-muted rounded-md text-xs">{{ model }}</pre>
      </div>
    </template>
  </ResourceLayout>
</template>
