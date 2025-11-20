<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import ResourceLayout from './../../../layouts/ResourceLayout.vue'
import FormRenderer from './../../../components/form/FormRenderer.vue'
import FormActions from './../../../components/form/FormActions.vue'
import PageHeaderActions from './../../../components/PageHeaderActions.vue'
import type { BackendBreadcrumb } from '@/composables/useBreadcrumbs'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

interface FormColumn {
  name: string
  label?: string
  component?: string
  required?: boolean
  [key: string]: any
}

interface Props {
  message?: string
  resourceLabel?: string
  breadcrumbs?: BackendBreadcrumb[]
  model?: any
  form?: {
    columns: FormColumn[]
    model?: Record<string, any>
    formActions?: any[]
  }
  pageHeaderActions?: any[]
}

const props = defineProps<Props>()

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  breadcrumbs: props.breadcrumbs,
}

// Inicializa o formulário com os valores do modelo
const formData = useForm(props.form?.model || props.model || {})

const handleSubmit = () => {
  formData.put(window.location.pathname, {
    preserveScroll: true,
    onSuccess: () => {
      // Formulário submetido com sucesso
    },
  })
}
</script>

<template>
  <ResourceLayout v-bind="layoutProps" title="Editar">
    <template #content>
      <div class="space-y-6">
        <Card class="rounded">
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Editar {{ resourceLabel }}</CardTitle>
                <CardDescription v-if="message">{{ message }}</CardDescription>
              </div>
              <PageHeaderActions :actions="pageHeaderActions" :model-id="model?.id" />
            </div>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="handleSubmit" class="space-y-6">
              <FormRenderer
                v-if="form?.columns"
                :columns="form.columns"
                v-model="formData"
                :errors="formData.errors"
              />

              <FormActions
                :actions="form?.formActions"
                :processing="formData.processing"
              />
            </form>
          </CardContent>
        </Card>
      </div>
    </template>
  </ResourceLayout>
</template>
