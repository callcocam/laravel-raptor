<!--
 * RepeaterActions - Action buttons for repeater field
 *
 * Add, clear, collapse all, expand all buttons
 -->
<template>
  <div class="flex items-center justify-between gap-2 mt-3">
    <!-- Left Actions -->
    <div class="flex items-center gap-2">
      <!-- Collapse/Expand All -->
      <template v-if="collapsible && totalItems > 0">
        <Button
          type="button"
          variant="outline"
          size="sm"
          @click="$emit('collapseAll')"
        >
          <ChevronsDown class="h-4 w-4 mr-2" />
          Recolher tudo
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          @click="$emit('expandAll')"
        >
          <ChevronsUp class="h-4 w-4 mr-2" />
          Expandir tudo
        </Button>
      </template>
    </div>

    <!-- Right Actions -->
    <div class="flex items-center gap-2">
      <!-- Clear All -->
      <Button
        v-if="totalItems > 0 && canClearAll"
        type="button"
        variant="outline"
        size="sm"
        class="text-destructive hover:text-destructive"
        @click="$emit('clearAll')"
      >
        <Trash2 class="h-4 w-4 mr-2" />
        Limpar tudo
      </Button>

      <!-- Add Button -->
      <Button
        v-if="canAdd"
        type="button"
        variant="default"
        size="sm"
        @click="$emit('add')"
      >
        <Plus class="h-4 w-4 mr-2" />
        {{ addButtonLabel }}
      </Button>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Plus, Trash2, ChevronsDown, ChevronsUp } from 'lucide-vue-next'

interface Props {
  totalItems: number
  canAdd: boolean
  canClearAll?: boolean
  collapsible?: boolean
  addButtonLabel: string
}

withDefaults(defineProps<Props>(), {
  canClearAll: true,
  collapsible: false,
})

defineEmits<{
  (e: 'add'): void
  (e: 'clearAll'): void
  (e: 'collapseAll'): void
  (e: 'expandAll'): void
  (e: 'click', event: Event): void
}>()
</script>
