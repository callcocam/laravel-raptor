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
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1.5 rounded-md border border-input bg-background px-3 text-sm hover:bg-accent hover:text-accent-foreground transition-colors"
          @click="$emit('collapseAll')"
        >
          <ChevronsDown class="h-4 w-4" />
          Recolher tudo
        </button>
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1.5 rounded-md border border-input bg-background px-3 text-sm hover:bg-accent hover:text-accent-foreground transition-colors"
          @click="$emit('expandAll')"
        >
          <ChevronsUp class="h-4 w-4" />
          Expandir tudo
        </button>
      </template>
    </div>

    <!-- Right Actions -->
    <div class="flex items-center gap-2">
      <!-- Clear All -->
      <button
        v-if="totalItems > 0 && canClearAll"
        type="button"
        class="inline-flex h-8 items-center gap-1.5 rounded-md border border-input bg-background px-3 text-sm text-destructive hover:bg-destructive/10 transition-colors"
        @click="$emit('clearAll')"
      >
        <Trash2 class="h-4 w-4" />
        Limpar tudo
      </button>

      <!-- Add Button -->
      <button
        v-if="canAdd"
        type="button"
        class="inline-flex h-8 items-center gap-1.5 rounded-md bg-primary px-3 text-sm text-primary-foreground hover:bg-primary/90 transition-colors"
        @click="$emit('add')"
      >
        <Plus class="h-4 w-4" />
        {{ addButtonLabel }}
      </button>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
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
