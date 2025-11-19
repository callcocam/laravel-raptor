<!--
 * DefaultBreadcrumb - Componente breadcrumb padrão
 * 
 * Usa componentes shadcn-vue para renderizar breadcrumbs
 * Suporta slot para header actions
 -->
<template>
  <div class="flex items-center justify-between w-full">
    <Breadcrumb>
      <BreadcrumbList>
        <!-- Home -->
        <BreadcrumbItem v-if="showHome">
          <BreadcrumbLink :href="homeHref">
            <component v-if="homeIcon" :is="homeIcon" class="h-4 w-4" />
            <span v-else>{{ homeLabel }}</span>
          </BreadcrumbLink>
        </BreadcrumbItem>
        
        <BreadcrumbSeparator v-if="showHome && visibleItems.length > 0" />

        <!-- Items visíveis -->
        <template v-for="(item, index) in visibleItems" :key="index">
          <BreadcrumbItem>
            <BreadcrumbLink v-if="item.href && !item.active" :href="item.href">
              <component v-if="item.icon" :is="item.icon" class="h-4 w-4 mr-1" />
              {{ item.label }}
            </BreadcrumbLink>
            <BreadcrumbPage v-else>
              <component v-if="item.icon" :is="item.icon" class="h-4 w-4 mr-1" />
              {{ item.label }}
            </BreadcrumbPage>
          </BreadcrumbItem>

          <BreadcrumbSeparator v-if="index < visibleItems.length - 1" />
        </template>

        <!-- Ellipsis se houver items ocultos -->
        <template v-if="hasHiddenItems">
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbEllipsis />
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          
          <!-- Último item -->
          <BreadcrumbItem v-if="lastItem">
            <BreadcrumbPage>
              <component v-if="lastItem.icon" :is="lastItem.icon" class="h-4 w-4 mr-1" />
              {{ lastItem.label }}
            </BreadcrumbPage>
          </BreadcrumbItem>
        </template>
      </BreadcrumbList>
    </Breadcrumb>

    <!-- Slot para Header Actions -->
    <div v-if="$slots.default" class="flex items-center gap-2">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { 
  Breadcrumb,
  BreadcrumbList,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbPage,
  BreadcrumbSeparator,
  BreadcrumbEllipsis
} from '@/components/ui/breadcrumb'
import { Home } from 'lucide-vue-next'
import type { BreadcrumbItemData, BreadcrumbConfigData } from './BreadcrumbRenderer.vue'

const props = withDefaults(defineProps<{
  items: BreadcrumbItemData[]
  config?: BreadcrumbConfigData
}>(), {
  items: () => [],
  config: () => ({})
})

// Configurações com defaults
const maxItems = computed(() => props.config?.maxItems || 3)
const showHome = computed(() => props.config?.showHome !== false)
const homeLabel = computed(() => props.config?.homeLabel || 'Home')
const homeHref = computed(() => props.config?.homeHref || '/')
const homeIcon = computed(() => props.config?.homeIcon || Home)

// Lógica de items visíveis
const hasHiddenItems = computed(() => props.items.length > maxItems.value)

const visibleItems = computed(() => {
  if (!hasHiddenItems.value) {
    return props.items
  }
  
  // Mostra primeiros e últimos items com ellipsis no meio
  return props.items.slice(0, maxItems.value - 1)
})

const lastItem = computed(() => {
  if (!hasHiddenItems.value) {
    return null
  }
  return props.items[props.items.length - 1]
})
</script>

