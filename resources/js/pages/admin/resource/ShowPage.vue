<script setup lang="ts">
import ResourceLayout from '~/layouts/ResourceLayout.vue'
import InfoRenderer from '~/components/infolist/InfoRenderer.vue'
import BreadcrumbRenderer from '~/components/breadcrumbs/BreadcrumbRenderer.vue' 
import type { BackendBreadcrumb } from '~/composables/useBreadcrumbs'
import { Card, CardContent } from '@/components/ui/card'
import PageHeaderActions from '~/components/PageHeaderActions.vue'

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
            <PageHeaderActions :actions="pageHeaderActions" :model-id="model?.id" />
          </BreadcrumbRenderer>
        </div>
      </div>
    </template>
    <template #content>
      <div class="space-y-6">
        <Card>
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
