<!--
 * ActionDropdown - Componente de dropdown de ações
 *
 * Renderiza um dropdown menu com múltiplas ações
 * Útil para agrupar ações relacionadas
 -->
<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button :variant="variant" :size="computedSize" class="gap-1.5">
        <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
        <span class="text-xs">{{ action.label }}</span>
        <ChevronDown class="h-3 w-3" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
      <DropdownMenuItem
        v-for="item in items"
        :key="item.name"
        class="gap-1.5"
        @click="() => handleItemClick(item)"
      >
        <component v-if="getItemIcon(item)" :is="getItemIcon(item)" class="h-3 w-3" />
        <span class="text-xs">{{ item.label }}</span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ChevronDown } from 'lucide-vue-next'
import * as LucideIcons from 'lucide-vue-next'
import { useActionUI } from '~/composables/useActionUI'
import type { TableAction } from '~/types/table'

interface Props {
  action: TableAction
  size?: 'default' | 'sm' | 'lg' | 'icon'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'sm'
})

const emit = defineEmits<{
  (e: 'click', item: any): void
}>()

// Items do dropdown (vindos de action.options)
const items = computed(() => {
  return props.action.options || []
})

// Usa composable para variant, iconComponent e iconClasses
const { variant, size: computedSize, iconComponent, iconClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
})

// Obtém ícone de um item
const getItemIcon = (item: any) => {
  if (!item.icon) return null

  const IconComponent = (LucideIcons as any)[item.icon]
  if (!IconComponent) return null

  return h(IconComponent)
}

// Handler de clique em item
const handleItemClick = (item: any) => {
  emit('click', item)
}
</script>
