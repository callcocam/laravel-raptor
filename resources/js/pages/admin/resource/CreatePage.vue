<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import ResourceLayout from '~/layouts/ResourceLayout.vue'
import FormRenderer from '~/components/form/FormRenderer.vue'
import FormActions from '~/components/form/FormActions.vue'
import BreadcrumbRenderer from '~/components/breadcrumbs/BreadcrumbRenderer.vue'
import type { BackendBreadcrumb } from '~/composables/useBreadcrumbs'
import { Card, CardContent } from '@/components/ui/card'
import PageHeaderActions from '~/components/PageHeaderActions.vue'

interface FormColumn {
  name: string
  label?: string
  component?: string
  required?: boolean
  default?: any
  [key: string]: any
}

interface Props {
  message?: string
  resourceLabel?: string
  breadcrumbs?: BackendBreadcrumb[]
  form?: {
    columns: FormColumn[]
    model?: Record<string, any>
    formActions?: any[]
  }
  pageHeaderActions?: any[],
  action?: string
}

const props = defineProps<Props>()

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  breadcrumbs: props.breadcrumbs,
}

// Inicializa o formulário: input antigo (após erro) > model do backend > defaults das colunas
const initialData = computed(() => {
  const data: Record<string, any> = {}

  props.form?.columns?.forEach(column => {
    if (column.default !== undefined && column.default !== null) {
      data[column.name] = column.default
    } else if (column.component === 'form-field-repeater') {
      data[column.name] = []
    }
  })

  if (props.form?.model && Object.keys(props.form.model).length > 0) {
    Object.assign(data, props.form.model)
  }

  return data
})

const formData = useForm(initialData.value)

// Calcula a URL de ação removendo /create do pathname
const action = computed(() => {
  if (props.action) {
    return props.action
  }

  const path = window.location.pathname
  if (path.endsWith('/create')) {
    return path.slice(0, -7) // Remove '/create'
  }
  return path
}) 
</script>

<template>
  <ResourceLayout v-bind="layoutProps" title="Criar">
    <template #header>
      <!-- Breadcrumbs com Header Actions -->
      <div v-if="breadcrumbs && breadcrumbs.length > 0" class="border-b bg-background">
        <div class="w-full flex items-center pb-4">
          <BreadcrumbRenderer :items="breadcrumbs" :config="{
            component: 'breadcrumb-page-header',
            resourceLabel: `Criar ${resourceLabel}`,
            message: message,
          }">
            <!-- Header Actions renderizadas ao lado dos breadcrumbs -->
            <PageHeaderActions :actions="pageHeaderActions" />
          </BreadcrumbRenderer>
        </div>
      </div>
    </template>
    <template #content>
      <div class="space-y-6">
        <Card>
          <CardContent>
            <FormRenderer v-if="form?.columns" :columns="form.columns" v-model="formData" :errors="formData.errors"
              :action="action" :method="'post'" @success="formData.reset()">
              <template #actions>
                <FormActions :actions="form?.formActions" :processing="formData.processing" />
              </template>
            </FormRenderer>
          </CardContent>
        </Card>
      </div>
    </template>
  </ResourceLayout>
</template>
