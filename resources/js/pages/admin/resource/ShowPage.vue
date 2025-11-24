<script setup lang="ts">
import ResourceLayout from './../../../layouts/ResourceLayout.vue'
import InfoRenderer from './../../../components/infolist/InfoReander.vue'
import PageHeaderActions from './../../../components/PageHeaderActions.vue'
import type { BackendBreadcrumb } from '@/composables/useBreadcrumbs'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

interface Props {
  message?: string
  resourceLabel?: string
  breadcrumbs?: BackendBreadcrumb[]
  model?: any
  infolist?: Record<string, any>
  pageHeaderActions?: any[]
}

const props = defineProps<Props>()

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  breadcrumbs: props.breadcrumbs,
}

// Filtra viewActions do infolist
const columns = Object.entries(props.infolist || {}).filter(
  ([key]) => key !== 'viewActions'
)
</script>

<template>
  <ResourceLayout v-bind="layoutProps" title="Visualizar">
    <template #content>
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>{{ resourceLabel }}</CardTitle>
                <CardDescription v-if="message">{{ message }}</CardDescription>
              </div>
              <PageHeaderActions :actions="pageHeaderActions" :model-id="model?.id" />
            </div>
          </CardHeader>
          <CardContent>
            <div class="grid gap-6">
              <div
                v-for="[key, column] in columns"
                :key="key"
                class="grid gap-2"
              >
                <label class="text-sm font-medium text-muted-foreground">
                  {{ column.label }}
                </label>
                <InfoRenderer :column="column" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </template>
  </ResourceLayout>
</template>
