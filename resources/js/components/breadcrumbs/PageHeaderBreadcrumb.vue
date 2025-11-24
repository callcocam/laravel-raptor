<!--
 * PageHeaderBreadcrumb - Breadcrumb com título e descrição integrados
 * 
 * Componente melhorado que combina breadcrumb com header da página
 * Ideal para páginas de recursos (Index, Create, Edit, Show)
 * Sem navegação duplicada - apenas título e ações
 -->
<template>
  <div class="w-full p-4 items-center">
    <!-- Header Section com Título e Ações -->
    <div class="flex items-center justify-between gap-4">
      <div class="space-y-2 flex-1 min-w-0">
        <h1 v-if="resourceLabel" class="text-3xl font-bold tracking-tight">
          {{ resourceLabel }}
        </h1>
        <p v-if="message" class="text-sm text-muted-foreground">
          {{ message }}
        </p>
      </div>

      <!-- Slot para Header Actions -->
      <div v-if="$slots.default" class="flex items-center gap-2 flex-shrink-0 ml-auto">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { BreadcrumbItemData, BreadcrumbConfigData } from "./BreadcrumbRenderer.vue";

interface Props {
  items?: BreadcrumbItemData[];
  config?: BreadcrumbConfigData;
}

const props = withDefaults(defineProps<Props>(), {
  items: () => [],
  config: () => ({}),
});

const resourceLabel = computed(() => {
  return props.config?.resourceLabel || "";
});

const message = computed(() => {
  return props.config?.message || "";
});
</script>
