<script setup lang="ts">
import ShowPage from '../resource/ShowPage.vue'
import type { BackendBreadcrumb } from '@/composables/useBreadcrumbs'

interface Props {
  message?: string
  resourceLabel?: string
  breadcrumbs?: BackendBreadcrumb[]
  model?: any
  infolist?: Record<string, any>
  pageHeaderActions?: any[]
}

defineProps<Props>()
</script>

<template>
  <ShowPage v-bind="$props" />
</template>
