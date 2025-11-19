<template>
  <a
    :href="`mailto:${column.text}`"
    class="text-primary hover:underline inline-flex items-center gap-2"
    :title="column.tooltip"
  >
    <Icon v-if="column.icon" :is="column.icon" class="h-4 w-4" />
    {{ column.text }}
  </a>
</template>

<script lang="ts" setup>
import Icon from '~/components/icon.vue'

defineProps<{
  column: {
    text: string
    icon?: string
    tooltip?: string
    type: string
  }
}>()
</script>
