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
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="space-y-2 flex-1 min-w-0 w-full sm:w-auto">
        <h1 v-if="resourceLabel" class="text-2xl sm:text-3xl font-bold tracking-tight break-words">
          {{ resourceLabel }}
        </h1>
        <p v-if="message" class="text-sm text-muted-foreground break-words">
          {{ message }}
        </p>
      </div>

      <!-- Slot para Header Actions -->
      <div v-if="$slots.default" class="flex items-center gap-2 flex-shrink-0 w-full sm:w-auto sm:ml-auto">
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
