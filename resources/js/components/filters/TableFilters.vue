<!--
 * TableFilters - Painel de filtros para tabelas
 * 
 * Renderiza múltiplos filtros em um layout responsivo e compacto
 * Atualiza a URL com os parâmetros de busca automaticamente
 * Suporta exibição inline ou em popover (preferência salva em cookie)
 -->
<template>
  <div v-if="filters && filters.length > 0" class="flex flex-col gap-2 mb-4 w-full">
    <!-- Barra de busca sempre visível -->
    <div class="flex items-center gap-2 w-full">
      <div class="relative flex-1">
        <Input  v-if="searchable" placeholder="Buscar..." class="w-full h-9 pr-10" v-model="filterValues.search"/>
        <Search v-if="searchable" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground h-4 w-4" />
      </div>

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

      <!-- Toggle inline/popover -->
      <Button
        v-if="hasAdvancedFilters"
        variant="ghost"
        size="sm"
        @click="toggleDisplayMode"
        type="button"
        class="flex-shrink-0"
        :title="currentDisplayMode === 'inline' ? 'Compactar filtros' : 'Expandir filtros'"
      >
        <SlidersHorizontal class="h-4 w-4" />
      </Button>

      <!-- Popover para filtros avançados -->
      <Popover v-if="hasAdvancedFilters && currentDisplayMode === 'popover'">
        <PopoverTrigger as-child>
          <Button variant="outline" size="sm" class="flex-shrink-0">
            Filtros
            <Badge v-if="activeFiltersCount > 0" variant="secondary" class="ml-2 h-5 px-1.5">
              {{ activeFiltersCount }}
            </Badge>
          </Button>
        </PopoverTrigger>
        <PopoverContent class="w-80 p-0" align="end">
          <div class="space-y-2 px-4 pt-4 pb-2 border-b border-border">
            <h4 class="font-medium text-sm">Filtros Avançados</h4>
            <p class="text-xs text-muted-foreground">Refine sua pesquisa</p>
          </div>
          <div class="max-h-[70vh] overflow-y-auto">
            <div class="space-y-3 p-4">
              <div v-for="filter in filters" :key="filter.name" class="w-full">
                <FilterRenderer
                  :filter="filter"
                  :modelValue="getFilterModelValue(filter)"
                  @update:modelValue="(value) => onFilterUpdate(filter, value)"
                  class="w-full"
                />
              </div>
            </div>
          </div>
        </PopoverContent>
      </Popover>
    </div>

    <!-- Filtros inline (quando modo inline está ativo) -->
    <Collapsible v-if="hasAdvancedFilters && currentDisplayMode === 'inline'" v-model:open="isInlineOpen">
      <CollapsibleTrigger as-child>
        <Button variant="outline" size="sm" class="w-full justify-between">
          <span class="flex items-center gap-2">
            <SlidersHorizontal class="h-4 w-4" />
            Filtros Avançados
            <Badge v-if="activeFiltersCount > 0" variant="secondary" class="h-5 px-1.5">
              {{ activeFiltersCount }}
            </Badge>
          </span>
          <ChevronDown class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': isInlineOpen }" />
        </Button>
      </CollapsibleTrigger>
      <CollapsibleContent>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 p-4 mt-2 bg-muted/30 rounded-md">
          <div v-for="filter in filters" :key="filter.name" class="w-full">
            <FilterRenderer
              :filter="filter"
              :modelValue="getFilterModelValue(filter)"
              @update:modelValue="(value) => onFilterUpdate(filter, value)"
              class="w-full"
            />
          </div>
        </div>
      </CollapsibleContent>
    </Collapsible>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Button } from "~/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Search, SlidersHorizontal, ChevronDown } from "lucide-vue-next";
import FilterRenderer from "./FilterRenderer.vue";
import { Input } from "@/components/ui/input";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible";
import { useDebounceFn } from "@vueuse/core";

interface Filter {
  name: string;
  label: string;
  type: string;
  component?: string;
  options?: Array<{ value: string | number; label: string }>;
  placeholder?: string;
  /** Campos do filtro em cascata (cada um vira um param na URL) */
  fields?: Array<{ name: string; label?: string; dependsOn?: string; [key: string]: any }>;
  /** Se presente, filtro cascata tem opção "Incluir pais"; param na URL (ex: category_id_include_parents) */
  includeParentsParam?: string;
  [key: string]: any;
}

interface Props {
  filters?: Filter[];
  isLoading?: boolean;
  searchable?: boolean;
  displayMode?: "inline" | "popover" | "auto"; // auto = usa cookie
  debounceMs?: number; // Tempo de debounce para campos de texto
}

interface Emits {
  (e: "apply", filters: Record<string, any>): void;
  (e: "clear"): void;
}

const props = withDefaults(defineProps<Props>(), {
  filters: () => [],
  isLoading: false,
  displayMode: "auto",
  debounceMs: 500,
});

const emit = defineEmits<Emits>();

const page = usePage();
const route = computed(() => new URL(page.url, window.location.origin));

// Estado interno dos filtros
const filterValues = ref<Record<string, any>>({});

// Estado do collapse inline
const isInlineOpen = ref(false);

// Cookies para preferência de exibição (por página)
const getCookieName = () => {
  // Usa o pathname da página atual para criar um cookie específico
  const pathname = window.location.pathname.replace(/\//g, '-').replace(/^-|-$/g, '') || 'home';
  return `table-filters-${pathname}`;
};

const getDisplayModeFromCookie = (): "inline" | "popover" => {
  if (typeof document === "undefined") return "popover";
  const cookieName = getCookieName();
  const cookie = document.cookie.split("; ").find((row) => row.startsWith(`${cookieName}=`));
  return (cookie?.split("=")[1] as "inline" | "popover") || "popover";
};

const setDisplayModeCookie = (mode: "inline" | "popover") => {
  if (typeof document === "undefined") return;
  const cookieName = getCookieName();
  // Cookie expira em 1 ano
  const expires = new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toUTCString();
  document.cookie = `${cookieName}=${mode}; expires=${expires}; path=/; SameSite=Lax`;
};

// Modo de exibição atual
const currentDisplayMode = ref<"inline" | "popover">(
  props.displayMode === "auto" ? getDisplayModeFromCookie() : props.displayMode as "inline" | "popover"
);

// Toggle do modo de exibição
const toggleDisplayMode = () => {
  const newMode = currentDisplayMode.value === "inline" ? "popover" : "inline";
  currentDisplayMode.value = newMode;
  if (props.displayMode === "auto") {
    setDisplayModeCookie(newMode);
  }
};

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

  // Inicializa o campo de busca
  if (params.search) {
    filterValues.value.search = params.search;
  }

  // Inicializa outros filtros
  props.filters?.forEach((filter) => {
    if (filter.fields?.length) {
      filter.fields.forEach((field) => {
        const queryValue = params[field.name];
        if (queryValue !== undefined && queryValue !== null && queryValue !== "") {
          filterValues.value[field.name] = queryValue;
        }
      });
      if (filter.includeParentsParam) {
        const v = params[filter.includeParentsParam];
        if (v !== undefined && v !== null && v !== "") {
          filterValues.value[filter.includeParentsParam] = v === "1" || v === "true" || v === true;
        }
      }
    } else {
      const queryValue = params[filter.name];
      if (queryValue !== undefined && queryValue !== null && queryValue !== "") {
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
 * Valor de modelValue a passar para cada filtro (suporta cascata com vários campos).
 */
const getFilterModelValue = (filter: Filter): any => {
  if (filter.fields?.length) {
    const acc = filter.fields.reduce<Record<string, any>>((a, field) => {
      a[field.name] = filterValues.value[field.name] ?? null;
      return a;
    }, {});
    if (filter.includeParentsParam) {
      const v = filterValues.value[filter.includeParentsParam];
      acc[filter.includeParentsParam] = v === true || v === "1" || v === "true";
    }
    return acc;
  }
  return filterValues.value[filter.name];
};

/**
 * Handler de update: filtro normal (name → value) ou cascata (payload { fieldName, value }).
 */
const onFilterUpdate = (filter: Filter, value: any) => {
  const isCascadingPayload =
    filter.fields?.length &&
    value !== null &&
    typeof value === "object" &&
    "fieldName" in value &&
    "value" in value;

  if (isCascadingPayload) {
    if (filter.includeParentsParam && value.fieldName === filter.includeParentsParam) {
      updateFilter(value.fieldName as string, value.value ? "1" : "");
      return;
    }
    updateCascadingField(filter, value.fieldName as string, value.value);
    return;
  }
  updateFilter(filter.name, value);
};

/**
 * Atualiza um campo de filtro em cascata: seta o valor e limpa os níveis abaixo; depois aplica.
 */
const updateCascadingField = (filter: Filter, fieldName: string, value: any) => {
  if (value === null || value === undefined || value === "") {
    delete filterValues.value[fieldName];
  } else {
    filterValues.value[fieldName] = value;
  }
  const fieldNames = filter.fields!.map((f) => f.name);
  const idx = fieldNames.indexOf(fieldName);
  for (let i = idx + 1; i < fieldNames.length; i++) {
    delete filterValues.value[fieldNames[i]];
  }
  applyFilters();
};

/**
 * Atualiza um filtro específico
 */
const updateFilter = (name: string, value: any) => {
  if (value === null || value === undefined || value === "") {
    delete filterValues.value[name];
  } else {
    filterValues.value[name] = value;
  }

  if (name !== "search") {
    applyFilters();
  } else {
    debouncedApplyFilters();
  }
};

/**
 * Aplica os filtros (atualiza URL e emite evento)
 */
const applyFilters = () => {
  const params = Object.fromEntries(new URLSearchParams(route.value.search));
  delete params.page;

  // Sincroniza params com filterValues: seta os ativos e remove os que foram limpos
  const filterKeys = new Set<string>();
  if (props.searchable) {
    filterKeys.add("search");
  }
  props.filters?.forEach((filter) => {
    if (filter.fields?.length) {
      filterKeys.add(filter.name);
      filter.fields.forEach((field) => filterKeys.add(field.name));
      if (filter.includeParentsParam) filterKeys.add(filter.includeParentsParam);
    } else {
      filterKeys.add(filter.name);
    }
  });

  filterKeys.forEach((key) => {
    const value = filterValues.value[key];
    if (value !== null && value !== undefined && value !== "") {
      if (typeof value === "object") {
        params[key] = JSON.stringify(value);
      } else {
        params[key] = value;
      }
    } else {
      delete params[key];
    }
  });

  router.get(window.location.pathname, params, { preserveState: true, replace: true });
  emit("apply", filterValues.value);
};

// Versão com debounce para campos de texto
const debouncedApplyFilters = useDebounceFn(applyFilters, props.debounceMs);

/**
 * Limpa todos os filtros
 */
const clearFilters = () => {
  filterValues.value = {};
  const params = Object.fromEntries(new URLSearchParams(route.value.search));
  // Remove todos os filtros da URL
  if (props.searchable) {
    delete params.search;
  }
  props.filters?.forEach((filter) => {
    if (filter.fields?.length) {
      delete params[filter.name];
      filter.fields.forEach((field) => delete params[field.name]);
      if (filter.includeParentsParam) delete params[filter.includeParentsParam];
    } else {
      delete params[filter.name];
    }
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

// Watch no campo de busca para aplicar com debounce
watch(
  () => filterValues.value.search,
  () => {
    debouncedApplyFilters();
  }
);
</script>
