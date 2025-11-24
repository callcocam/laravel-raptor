<script setup lang="ts">
import CreatePage from '../resource/CreatePage.vue'
import type { BackendBreadcrumb } from '@/composables/useBreadcrumbs'

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

defineProps<Props>()
</script>

<template>
  <CreatePage v-bind="$props" />
</template>
