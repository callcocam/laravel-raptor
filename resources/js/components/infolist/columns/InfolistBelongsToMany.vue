<template>
  <div class="space-y-2">
    <!-- Items as badges/tags -->
    <div v-if="column.items && column.items.length > 0" class="flex flex-wrap gap-2">
      <div
        v-for="(item, index) in column.items"
        :key="item.id || index"
        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-muted text-sm hover:bg-muted/80 transition-colors"
      >
        <Icon v-if="column.icon" :is="column.icon" class="h-3 w-3 text-muted-foreground" />
        <span>{{ item.display }}</span>

        <!-- Actions -->
        <div v-if="item.actions && item.actions.length > 0" class="flex items-center gap-0.5 ml-1">
          <ActionRenderer
            v-for="action in item.actions"
            :key="action.name"
            :action="action"
            :record="item.data"
          />
        </div>
      </div>

      <!-- Show more indicator -->
      <div v-if="column.hasMore" class="inline-flex items-center px-3 py-1 rounded-full bg-muted/50 text-xs text-muted-foreground">
        +{{ column.total - column.items.length }}
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-sm text-muted-foreground flex items-center gap-2">
      <Icon is="Inbox" class="h-4 w-4" />
      <span>Nenhum item relacionado</span>
    </div>

    <!-- Total count -->
    <div v-if="column.total > 0" class="text-xs text-muted-foreground">
      {{ column.total }} {{ column.total === 1 ? 'item' : 'itens' }}
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
