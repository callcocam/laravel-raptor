<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
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
  pageHeaderActions?: any[]
}

const props = defineProps<Props>()

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  breadcrumbs: props.breadcrumbs,
}

// Inicializa o formulário com valores padrão dos campos
const initialData = computed(() => {
  const data: Record<string, any> = {}
  
  props.form?.columns?.forEach(column => {
    if (column.default !== undefined && column.default !== null) {
      data[column.name] = column.default
    }
  })
  
  return data
})

const formData = useForm(initialData.value)

const handleSubmit = () => {
  formData.post(window.location.pathname.replace('/create', ''), {
    preserveScroll: true,
    onSuccess: () => {
      // Formulário submetido com sucesso
      formData.reset()
    },
  })
}
</script>

<template>
  <ResourceLayout v-bind="layoutProps" title="Criar">
    <template #content>
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Criar {{ resourceLabel }}</CardTitle>
                <CardDescription v-if="message">{{ message }}</CardDescription>
              </div>
              <PageHeaderActions :actions="pageHeaderActions" />
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
