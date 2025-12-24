<template>
  <div class="space-y-2">
    <div v-if="column.items && column.items.length > 0" class="space-y-1">
      <div
        v-for="(item, index) in column.items"
        :key="item.id || index"
        class="flex items-center justify-between gap-2 p-2 rounded-md hover:bg-muted/50 transition-colors"
      >
        <div class="flex items-center gap-2 flex-1 min-w-0">
          <Icon v-if="column.icon" :is="column.icon" class="h-4 w-4 text-muted-foreground flex-shrink-0" />
          <span class="text-sm truncate">{{ item.display }}</span>
        </div>

        <!-- Actions -->
        <div v-if="item.actions && item.actions.length > 0" class="flex items-center gap-1">
          <ActionRenderer
            v-for="action in item.actions"
            :key="action.name"
            :action="action"
            :record="item.data"
          />
        </div>
      </div>

      <!-- Show more indicator -->
      <div v-if="column.hasMore" class="text-xs text-muted-foreground px-2 py-1">
        +{{ column.total - column.items.length }} mais...
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-sm text-muted-foreground flex items-center gap-2">
      <Icon is="Inbox" class="h-4 w-4" />
      <span>Nenhum item encontrado</span>
    </div>

    <!-- Total count -->
    <div v-if="column.total > 0" class="text-xs text-muted-foreground">
      Total: {{ column.total }} {{ column.total === 1 ? 'item' : 'itens' }}
    </div>
  </div>
</template>

<script lang="ts" setup>
import Icon from '~/components/icon.vue'
import ActionRenderer from '~/components/actions/ActionRenderer.vue'

defineProps<{
  column: {
    items: Array<{
      id: string | number | null
      display: string
      data: any
      actions?: Array<any>
    }>
    hasMore: boolean
    total: number
    icon?: string
    tooltip?: string
    type: string
    displayField: string
  }
}>()
</script>
