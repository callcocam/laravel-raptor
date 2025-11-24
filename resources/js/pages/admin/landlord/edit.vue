<script setup lang="ts">
import EditPage from '../resource/EditPage.vue'
import type { BackendBreadcrumb } from '@/composables/useBreadcrumbs'

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
  action?: string
}

defineProps<Props>()
</script>

<template>
  <EditPage v-bind="$props" />
</template>
