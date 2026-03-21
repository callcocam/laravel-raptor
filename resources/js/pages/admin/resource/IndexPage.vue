<script setup lang="ts">
import { computed } from 'vue'
import TableRegistry from '~/utils/TableRegistry'
import ResourceLayout from "~/layouts/ResourceLayout.vue";
import type { BackendBreadcrumb } from '~/composables/useBreadcrumbs'
import HeaderActions from '~/components/table/HeaderActions.vue'
import BackendTabs from '~/components/table/BackendTabs.vue'

interface BackendTab {
  key: string;
  name: string;
  href: string;
  icon?: string;
  badge?: number | null;
  active?: boolean;
}

interface Props {
  message?: string;
  resourceLabel?: string;
  resourcePluralLabel?: string;
  breadcrumbs?: BackendBreadcrumb[];
  headerActions?: any;
  table?: any;
  tabs?: BackendTab[];
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
  return registeredComponent ?? TableRegistry.get('table-default')
})

const tableTabs = computed<BackendTab[] | undefined>(() => props.table?.tabs ?? props.tabs ?? undefined)
</script>

<template>
  <ResourceLayout v-bind="layoutProps" :title="actionName || 'List'">
    <template v-if="headerActions && headerActions.length" #header-actions>
      <HeaderActions :actions="headerActions" />
    </template>
    <template #content>
      <div class="space-y-4">
        <BackendTabs v-if="tableTabs?.length" :tabs="tableTabs" current-tab="list" />
        <component :is="getComponent" v-bind="table?.props" />
      </div>
    </template>
  </ResourceLayout>
</template>
