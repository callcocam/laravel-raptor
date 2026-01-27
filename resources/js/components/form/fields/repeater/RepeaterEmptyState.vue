<!--
 * RepeaterEmptyState - Empty state when no items
 -->
<template>
  <div class="border-2 border-dashed rounded-lg p-8 text-center">
    <component :is="emptyIcon" class="h-12 w-12 mx-auto mb-3 text-muted-foreground/50" />
    <p class="text-muted-foreground font-medium mb-1">
      {{ emptyTitle }}
    </p>
    <p class="text-sm text-muted-foreground/70">
      {{ emptyDescription }}
    </p>
    <div class="mt-4 flex justify-center items-center gap-x-2">
      <Button v-if="showAddButton" type="button" variant="outline" size="sm" @click="$emit('add')">
        <Plus class="h-4 w-4 mr-2" />
        {{ addButtonLabel }}
      </Button>
      <template v-if="actions.length > 0">
        <ActionRenderer v-for="(action, index) in actions" :key="index" :action="action" :column="column" />
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Plus, PackageOpen } from 'lucide-vue-next'
import ActionRenderer from '~/components/actions/ActionRenderer.vue'

interface RepeaterColumn {
  emptyTitle?: string
  emptyDescription?: string
  addButtonLabel?: string
  actions?: any[]
  [key: string]: any
}

interface Props {
  column: RepeaterColumn
  showAddButton?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showAddButton: true
})

defineEmits<{
  (e: 'add'): void
}>()

const emptyIcon = PackageOpen

// Computed properties extraídas da column
const emptyTitle = computed(() => props.column.emptyTitle || 'Nenhum item adicionado')
const emptyDescription = computed(() => props.column.emptyDescription || 'Clique no botão abaixo para adicionar o primeiro item')
const addButtonLabel = computed(() => props.column.addButtonLabel || 'Adicionar item')
const actions = computed(() => props.column.actions || [])
</script>
