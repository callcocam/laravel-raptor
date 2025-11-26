<!--
 * TableFilters - Painel de filtros para tabelas
 * 
 * Renderiza múltiplos filtros em um layout responsivo e compacto
 * Atualiza a URL com os parâmetros de busca
 * Pode ser usado independentemente ou com tabelas
 -->
<template>
  <div v-if="filters && filters.length > 0" class="flex flex-col gap-2 mb-4">
    <!-- Barra de busca sempre visível -->
    <div class="flex items-center gap-2 w-full">
      <div class="relative flex-1" v-if="searchable">
        <Input placeholder="Buscar..." class="w-full h-9 pr-10" v-model="filterValues.search"/>
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground h-4 w-4" />
      </div>

      <Button
        type="button"
        v-if="!autoApply"
        size="sm"
        @click="applyFilters"
        :disabled="isLoading"
        class="flex-shrink-0"
      >
        <Search class="mr-2 h-4 w-4" />
        Aplicar
      </Button>

      <Button
        v-if="hasActiveFilters"
        variant="ghost"
        size="sm"
        @click="clearFilters"
        type="button"
        class="flex-shrink-0"
      >
        Limpar
      </Button>

      <!-- Popover para filtros avançados -->
      <Popover v-if="hasAdvancedFilters">
        <PopoverTrigger as-child>
          <Button variant="outline" size="sm" class="flex-shrink-0">
            <SlidersHorizontal class="mr-2 h-4 w-4" />
            Filtros
            <Badge v-if="activeFiltersCount > 0" variant="secondary" class="ml-2 h-5 px-1.5">
              {{ activeFiltersCount }}
            </Badge>
          </Button>
        </PopoverTrigger>
        <PopoverContent class="w-80 p-4" align="end">
          <div class="space-y-4 w-full">
            <div class="space-y-2">
              <h4 class="font-medium text-sm">Filtros Avançados</h4>
              <p class="text-xs text-muted-foreground">Refine sua pesquisa</p>
            </div>
            
            <div class="space-y-3">
              <div v-for="filter in filters" :key="filter.name" class="w-full">
                <FilterRenderer
                  :filter="filter"
                  :modelValue="filterValues[filter.name]"
                  @update:modelValue="(value) => updateFilter(filter.name, value)"
                  class="w-full"
                />
              </div>
            </div>
          </div>
        </PopoverContent>
      </Popover>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Search, SlidersHorizontal } from "lucide-vue-next";
import FilterRenderer from "./FilterRenderer.vue";
import { Input } from "@/components/ui/input";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";

interface Filter {
  name: string;
  label: string;
  type: string;
  component?: string;
  options?: Array<{ value: string | number; label: string }>;
  placeholder?: string;
  [key: string]: any;
}

interface Props {
  filters?: Filter[];
  autoApply?: boolean; // Se true, aplica filtros automaticamente ao mudar
  isLoading?: boolean;
  searchable?: boolean;
}

interface Emits {
  (e: "apply", filters: Record<string, any>): void;
  (e: "clear"): void;
}

const props = withDefaults(defineProps<Props>(), {
  filters: () => [],
  autoApply: false,
  isLoading: false,
});

const emit = defineEmits<Emits>();

const page = usePage();
const route = computed(() => new URL(page.url, window.location.origin));

// Estado interno dos filtros
const filterValues = ref<Record<string, any>>({});

// Verifica se tem filtros avançados (além do search)
const hasAdvancedFilters = computed(() => {
  return props.filters && props.filters.length > 0;
});

// Conta quantos filtros avançados estão ativos (excluindo search)
const activeFiltersCount = computed(() => {
  return Object.entries(filterValues.value).filter(([key, value]) => {
    if (key === 'search') return false; // Ignora o campo de busca
    if (value === null || value === undefined || value === "") return false;
    if (typeof value === "object") {
      return Object.values(value).some((v) => v !== null && v !== undefined && v !== "");
    }
    return true;
  }).length;
});

// Inicializa valores dos filtros da URL
const initializeFromQuery = () => {
  const params = Object.fromEntries(new URLSearchParams(route.value.search));
  props.filters?.forEach((filter) => {
    const queryValue = params[filter.name];
    if (queryValue !== undefined && queryValue !== null && queryValue !== "") {
      // Para date range, tenta fazer parse do JSON
      if (filter.type === "date-range" && typeof queryValue === "string") {
        try {
          filterValues.value[filter.name] = JSON.parse(queryValue);
        } catch {
          filterValues.value[filter.name] = queryValue;
        }
      } else {
        filterValues.value[filter.name] = queryValue;
      }
    }
  });
};

// Inicializa na montagem
initializeFromQuery();

// Verifica se tem filtros ativos
const hasActiveFilters = computed(() => {
  return Object.values(filterValues.value).some((value) => {
    if (value === null || value === undefined || value === "") return false;
    if (typeof value === "object") {
      // Para date range
      return Object.values(value).some((v) => v !== null && v !== undefined && v !== "");
    }
    return true;
  });
});

/**
 * Atualiza um filtro específico
 */
const updateFilter = (name: string, value: any) => {
  if (value === null || value === undefined || value === "") {
    delete filterValues.value[name];
  } else {
    filterValues.value[name] = value;
  }

  // Se autoApply está ativado, aplica imediatamente
  if (props.autoApply) {
    applyFilters();
  }
};

/**
 * Aplica os filtros (atualiza URL e emite evento)
 */
const applyFilters = () => {
  const params = Object.fromEntries(new URLSearchParams(route.value.search));
  // Remove page ao aplicar filtros (volta para página 1)
  delete params.page;
  // Adiciona os filtros ativos
  Object.entries(filterValues.value).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== "") {
      // Para date range e objetos, serializa como JSON
      if (typeof value === "object") {
        params[key] = JSON.stringify(value);
      } else {
        params[key] = value;
      }
    } else {
      delete params[key];
    }
  });
  // Atualiza URL e faz request Inertia
  router.get(window.location.pathname, params, { preserveState: true, replace: true });
  // Emite evento
  emit("apply", filterValues.value);
};

/**
 * Limpa todos os filtros
 */
const clearFilters = () => {
  filterValues.value = {};
  const params = Object.fromEntries(new URLSearchParams(route.value.search));
  // Remove todos os filtros da URL
  props.filters?.forEach((filter) => {
    delete params[filter.name];
  });
  // Remove page também
  delete params.page;
  // Atualiza URL e faz request Inertia
  router.get(window.location.pathname, params, { preserveState: true, replace: true });
  // Emite evento
  emit("clear");
};

// Watch na rota para reagir a mudanças externas (ex: botão voltar do navegador)
watch(
  () => route.value.search,
  () => {
    initializeFromQuery();
  }
);
</script>
