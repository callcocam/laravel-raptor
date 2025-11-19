<template>
  <a
    v-if="column.text"
    :href="`tel:${cleanPhone}`"
    class="text-primary hover:underline inline-flex items-center gap-2"
    :title="column.tooltip"
  >
    <Icon v-if="column.icon" :is="column.icon" class="h-4 w-4" />
    {{ column.text }}
  </a>
  <span v-else class="text-muted-foreground">-</span>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import Icon from '~/components/icon.vue'

const props = defineProps<{
  column: {
    text: string
    icon?: string
    tooltip?: string
    type: string
  }
}>()

// Remove formatação para o link tel:
const cleanPhone = computed(() => {
  return props.column.text?.replace(/\D/g, '') || ''
})
</script>
