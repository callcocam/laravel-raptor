<script setup lang="ts">
import ResourceLayout from "./../../../layouts/ResourceLayout.vue";
import DefaultTable from "~/components/table/DefaultTable.vue";
import type { BackendBreadcrumb } from "@/composables/useBreadcrumbs";
import HeaderActions from "~/components/table/HeaderActions.vue";
import BreadcrumbRenderer from "~/components/breadcrumbs/BreadcrumbRenderer.vue";

interface Props {
  message?: string;
  resourceLabel?: string;
  breadcrumbs?: BackendBreadcrumb[];
  headerActions?: any;
  table?: any;
}

const props = defineProps<Props>();

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  breadcrumbs: props.breadcrumbs,
};
</script>

<template>
  <ResourceLayout v-bind="layoutProps" title="Dashboard">
    <template #header>
      <!-- Breadcrumbs com Header Actions -->
      <div
        v-if="breadcrumbs && breadcrumbs.length > 0"
        class="border-b bg-background"
      >
        <div class="w-full flex items-center pb-4">
          <BreadcrumbRenderer :items="breadcrumbs" :config="{}">
            <!-- Header Actions renderizadas ao lado dos breadcrumbs -->
            
          </BreadcrumbRenderer>
        </div>
      </div>
    </template>
    <template #content>
      <div class="space-y-4">
        <div v-if="resourceLabel || message">
          <h1 v-if="resourceLabel" class="text-2xl font-bold">{{ resourceLabel }}</h1>
          <p v-if="message" class="text-muted-foreground mt-1">{{ message }}</p>
        </div>

        <DefaultTable />
      </div>
    </template>
  </ResourceLayout>
</template>
