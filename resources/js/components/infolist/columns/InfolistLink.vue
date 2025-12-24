<template>
  <component
    :is="column.isExternal ? 'a' : Link"
    :href="column.url"
    :target="column.openInNewTab ? '_blank' : undefined"
    :rel="column.openInNewTab ? 'noopener noreferrer' : undefined"
    class="text-primary hover:underline inline-flex items-center gap-2 transition-colors"
    :title="column.tooltip"
  >
    <Icon v-if="column.icon" :is="column.icon" class="h-4 w-4" />
    {{ column.text }}
    <Icon v-if="column.openInNewTab" is="ExternalLink" class="h-3 w-3" />
  </component>
</template>

<script lang="ts" setup>
import { Link } from '@inertiajs/vue3'
import Icon from '~/components/icon.vue'

defineProps<{
  column: {
    text: string
    url: string
    icon?: string
    tooltip?: string
    type: string
    openInNewTab?: boolean
    isExternal?: boolean
  }
}>()
</script>
