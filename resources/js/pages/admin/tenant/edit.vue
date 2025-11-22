<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import ResourceLayout from "./../../../layouts/ResourceLayout.vue";
import FormRenderer from "./../../../components/form/FormRenderer.vue";
import FormActions from "./../../../components/form/FormActions.vue";
import PageHeaderActions from "./../../../components/PageHeaderActions.vue";
import type { BackendBreadcrumb } from "@/composables/useBreadcrumbs";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

interface FormColumn {
  name: string;
  label?: string;
  component?: string;
  required?: boolean;
  [key: string]: any;
}

interface Props {
  message?: string;
  resourceLabel?: string;
  breadcrumbs?: BackendBreadcrumb[];
  model?: any;
  form?: {
    columns: FormColumn[];
    model?: Record<string, any>;
    formActions?: any[];
  };
  pageHeaderActions?: any[];
  action?: string;
}

const props = defineProps<Props>();

const layoutProps = {
  message: props.message,
  resourceLabel: props.resourceLabel,
  breadcrumbs: props.breadcrumbs,
};

// Inicializa o formulário Inertia com os valores do modelo
const formData = useForm(props.form?.model || props.model || {})

// Verifica se há arquivos no formData (recursivamente)
const hasFiles = (obj: any = formData.data()): boolean => {
  if (!obj || typeof obj !== 'object') return false
  
  // Verifica File
  if (obj instanceof File) return true
  
  // Verifica FileList
  if (obj instanceof FileList) return obj.length > 0
  
  // Verifica Blob
  if (obj instanceof Blob) return true
  
  // Verifica arrays
  if (Array.isArray(obj)) {
    return obj.some(item => hasFiles(item))
  }
  
  // Verifica objetos recursivamente
  return Object.values(obj).some(value => hasFiles(value))
}

const handleSubmit = () => {
  const formDataObj = formData.data()
  const hasFileUploads = hasFiles()
  
  console.log('=== FORM SUBMISSION DEBUG ===')
  console.log('Submitting form, hasFiles:', hasFileUploads)
  console.log('Form data keys:', Object.keys(formDataObj))
  
  // Log detalhado de cada campo
  Object.entries(formDataObj).forEach(([key, value]) => {
    if (value instanceof File) {
      console.log(`Field "${key}" is a File:`, value.name, value.type, value.size)
    } else if (value instanceof FileList) {
      console.log(`Field "${key}" is a FileList with ${value.length} files`)
    } else if (value instanceof Blob) {
      console.log(`Field "${key}" is a Blob:`, value.type, value.size)
    } else if (Array.isArray(value)) {
      console.log(`Field "${key}" is an Array:`, value.length, 'items')
      value.forEach((item, idx) => {
        if (item instanceof File) {
          console.log(`  [${idx}] File:`, item.name)
        }
      })
    } else {
      console.log(`Field "${key}":`, typeof value, value === null ? 'null' : value === undefined ? 'undefined' : '')
    }
  })
  
  console.log('=========================')
  
  // Se há arquivos, usa POST com _method=PUT (method spoofing)
  // Porque PUT não suporta multipart/form-data nativamente
  if (hasFileUploads) {
    formData.transform((data) => ({
      ...data,
      _method: 'PUT',
    })).post(props.action, {
      preserveScroll: true,
      preserveState: true,
      forceFormData: true,
      onSuccess: () => {
        console.log('Form submitted successfully with files')
      },
      onError: (errors) => {
        console.error('Form submission errors:', errors)
      },
    })
  } else {
    // Sem arquivos, usa PUT normal
    formData.put(props.action, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        console.log('Form submitted successfully')
      },
      onError: (errors) => {
        console.error('Form submission errors:', errors)
      },
    })
  }
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
            <FormRenderer
              v-if="form?.columns"
              :columns="form.columns"
              v-model="formData"
              :errors="formData.errors"
              @submit="handleSubmit"
            >
              <template #actions>
                <FormActions
                  :actions="form?.formActions"
                  :processing="formData.processing"
                />
              </template>
            </FormRenderer>
          </CardContent>
        </Card>
      </div>
    </template>
  </ResourceLayout>
</template>
