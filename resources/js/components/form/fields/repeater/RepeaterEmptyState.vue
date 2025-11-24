<!--
 * RepeaterEmptyState - Empty state when no items
 -->
<template>
  <div class="border-2 border-dashed rounded-lg p-8 text-center">
    <component
      :is="emptyIcon"
      class="h-12 w-12 mx-auto mb-3 text-muted-foreground/50"
    />
    <p class="text-muted-foreground font-medium mb-1">
      {{ emptyTitle }}
    </p>
    <p class="text-sm text-muted-foreground/70">
      {{ emptyDescription }}
    </p>
    <Button
      v-if="showAddButton"
      type="button"
      variant="outline"
      size="sm"
      class="mt-4"
      @click="$emit('add')"
    >
      <Plus class="h-4 w-4 mr-2" />
      {{ addButtonLabel }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Plus, PackageOpen } from 'lucide-vue-next'

interface Props {
  emptyTitle?: string
  emptyDescription?: string
  addButtonLabel?: string
  showAddButton?: boolean
}

withDefaults(defineProps<Props>(), {
  emptyTitle: 'Nenhum item adicionado',
  emptyDescription: 'Clique no botão abaixo para adicionar o primeiro item',
  addButtonLabel: 'Adicionar item',
  showAddButton: true,
})

defineEmits<{
  (e: 'add'): void
}>()

const emptyIcon = PackageOpen
</script>
