<script setup lang="ts">
import IndexPage from '../resource/IndexPage.vue'
import type { BackendBreadcrumb } from '@/composables/useBreadcrumbs'

interface Props {
  message?: string
  resourceLabel?: string
  breadcrumbs?: BackendBreadcrumb[]
  headerActions?: any
  table?: any
}

defineProps<Props>()
</script>

<template>
  <IndexPage v-bind="$props" />
</template>
