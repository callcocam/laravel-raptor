<script setup lang="ts">
import { computed } from 'vue'
import TableRegistry from '~/utils/TableRegistry'
import ResourceLayout from "~/layouts/ResourceLayout.vue";
import type { BackendBreadcrumb } from '~/composables/useBreadcrumbs'
import BreadcrumbRenderer from "~/components/breadcrumbs/BreadcrumbRenderer.vue";
import HeaderActions from "~/components/table/HeaderActions.vue";

interface Props {
  message?: string;
  resourceLabel?: string;
  resourcePluralLabel?: string;
  breadcrumbs?: BackendBreadcrumb[];
  headerActions?: any;
  table?: any;
  action?: string;
  actionName?: string;
}

const props = defineProps<Props>();

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  resourcePluralLabel: props.resourcePluralLabel,
  breadcrumbs: props.breadcrumbs,
};  
const getComponent = computed(() => {
  const componentName = props.table?.component || 'table-default'

  const registeredComponent = TableRegistry.get(componentName)
  if (registeredComponent) {
    return registeredComponent
  }

  return TableRegistry.get('table-default')
})
</script>

<template>
  <ResourceLayout v-bind="layoutProps" :title="actionName || 'List'">
    <template #header>
      <!-- Breadcrumbs com Header Actions -->
      <div v-if="breadcrumbs && breadcrumbs.length > 0" class="border-b bg-background">
        <div class="w-full flex items-center pb-4">
          <BreadcrumbRenderer
            :items="breadcrumbs"
            :config="{
              component: 'breadcrumb-page-header',
              resourceLabel: resourceLabel,
              message: message,
            }"
          >
            <!-- Header Actions renderizadas ao lado dos breadcrumbs -->
            <HeaderActions
              v-if="table.headerActions && table.headerActions.length"
              :actions="table.headerActions"
            />
          </BreadcrumbRenderer>
        </div>
      </div>
    </template>
    <template #content>
      <div class="space-y-4">  
         <component :is="getComponent" v-bind="table.props" /> 
      </div>
    </template>
  </ResourceLayout>
</template>
