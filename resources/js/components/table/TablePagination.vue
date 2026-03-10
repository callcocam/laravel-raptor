<template>
  <div v-if="meta.last_page > 1" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 px-2 py-4 border-t">
    <!-- Info de registros - oculta no mobile -->
    <div class="hidden sm:block text-sm text-muted-foreground">
      Mostrando <span class="font-medium">{{ meta.from }}</span> até
      <span class="font-medium">{{ meta.to }}</span> de
      <span class="font-medium">{{ meta.total }}</span> registros
    </div>

    <!-- Paginação responsiva -->
    <div class="flex items-center gap-2 w-full sm:w-auto justify-center">
      <!-- Mobile: apenas botões de navegação -->
      <div class="flex sm:hidden items-center gap-2">
        <!-- Primeiro -->
        <Link
          v-if="firstPageUrl"
          :href="firstPageUrl"
          :preserve-state="true"
          :preserve-scroll="true"
          class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-600 bg-slate-800 text-white shadow-xs transition-all hover:border-slate-500 hover:bg-slate-700 btn-gradient"
        >
          <ActionIconBox variant="default" class="[&_svg]:size-4">
            <ChevronsLeft />
          </ActionIconBox>
        </Link>
        <span v-else class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-600 bg-slate-800/50 text-white/50">
          <ChevronsLeft class="size-4" />
        </span>

        <!-- Anterior -->
        <Link
          v-if="prevPageUrl"
          :href="prevPageUrl"
          :preserve-state="true"
          :preserve-scroll="true"
          class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-600 bg-slate-800 text-white shadow-xs transition-all hover:border-slate-500 hover:bg-slate-700 btn-gradient"
        >
          <ActionIconBox variant="default" class="[&_svg]:size-4">
            <ChevronLeft />
          </ActionIconBox>
        </Link>
        <span v-else class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-600 bg-slate-800/50 text-white/50">
          <ChevronLeft class="size-4" />
        </span>

        <!-- Indicador de página atual (destaque verde) -->
        <span class="inline-flex h-8 min-w-[4rem] items-center justify-center rounded-xl border-2 border-primary bg-primary px-3 text-sm font-semibold text-primary-foreground shadow-md ring-2 ring-primary/30">
          {{ meta.current_page }} / {{ meta.last_page }}
        </span>

        <!-- Próximo -->
        <Link
          v-if="nextPageUrl"
          :href="nextPageUrl"
          :preserve-state="true"
          :preserve-scroll="true"
          class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-600 bg-slate-800 text-white shadow-xs transition-all hover:border-slate-500 hover:bg-slate-700 btn-gradient"
        >
          <ActionIconBox variant="default" class="[&_svg]:size-4">
            <ChevronRight />
          </ActionIconBox>
        </Link>
        <span v-else class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-600 bg-slate-800/50 text-white/50">
          <ChevronRight class="size-4" />
        </span>

        <!-- Último -->
        <Link
          v-if="lastPageUrl"
          :href="lastPageUrl"
          :preserve-state="true"
          :preserve-scroll="true"
          class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-600 bg-slate-800 text-white shadow-xs transition-all hover:border-slate-500 hover:bg-slate-700 btn-gradient"
        >
          <ActionIconBox variant="default" class="[&_svg]:size-4">
            <ChevronsRight />
          </ActionIconBox>
        </Link>
        <span v-else class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-600 bg-slate-800/50 text-white/50">
          <ChevronsRight class="size-4" />
        </span>
      </div>

      <!-- Desktop: todos os links de paginação -->
      <div class="hidden sm:flex items-center gap-2">
        <template v-for="(link, index) in paginationLinks" :key="index">
          <!-- Página ativa (destaque verde) -->
          <span
            v-if="link.active"
            class="inline-flex h-8 min-w-[2.25rem] shrink-0 items-center justify-center rounded-xl border-2 border-primary bg-primary px-2.5 text-sm font-semibold text-primary-foreground shadow-md ring-2 ring-primary/30"
          >
            <span v-html="link.label"></span>
          </span>
          <!-- Link clicável -->
          <Link
            v-else-if="link.url"
            :href="link.url"
            :preserve-state="true"
            :preserve-scroll="true"
            class="inline-flex h-8 min-w-[2.25rem] shrink-0 items-center justify-center rounded-xl border border-slate-600 bg-slate-800 px-2.5 text-sm font-medium text-white shadow-xs transition-all hover:border-slate-500 hover:bg-slate-700 btn-gradient"
          >
            <span v-html="link.label"></span>
          </Link>
          <!-- Reticências -->
          <span
            v-else
            class="inline-flex h-8 min-w-[2.25rem] items-center justify-center px-2.5 text-sm text-muted-foreground"
          >
            …
          </span>
        </template>
      </div>
    </div>

    <!-- Seletor de itens por página -->
    <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
      <span class="text-sm text-muted-foreground">Por página</span>
      <Select :model-value="String(meta.per_page)" @update:model-value="changePerPage">
        <SelectTrigger class="h-8 w-[70px]">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="size in [10, 15, 25, 50, 100]" :key="size" :value="String(size)">
            {{ size }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import ActionIconBox from '~/components/ui/ActionIconBox.vue'
import { TableMeta } from '~/types/table';


interface Props {
  meta: TableMeta
}

const props = defineProps<Props>()

const paginationLinks = computed(() => {
  const currentParams = new URLSearchParams(window.location.search)
  const perPage = currentParams.get('per_page') || props.meta.per_page

  return props.meta.links.map(link => {
    if (!link.url) return link

    const url = new URL(link.url, window.location.origin)
    url.searchParams.set('per_page', String(perPage))

    currentParams.forEach((value, key) => {
      if (key !== 'page' && key !== 'per_page') {
        url.searchParams.set(key, value)
      }
    })

    return {
      ...link,
      url: url.pathname + url.search
    }
  })
})

// URLs para navegação mobile
const firstPageUrl = computed(() => {
  if (props.meta.current_page === 1) return null
  
  const currentParams = new URLSearchParams(window.location.search)
  currentParams.set('page', '1')
  currentParams.set('per_page', String(props.meta.per_page))
  
  return window.location.pathname + '?' + currentParams.toString()
})

const prevPageUrl = computed(() => {
  if (props.meta.current_page === 1) return null
  
  const currentParams = new URLSearchParams(window.location.search)
  currentParams.set('page', String(props.meta.current_page - 1))
  currentParams.set('per_page', String(props.meta.per_page))
  
  return window.location.pathname + '?' + currentParams.toString()
})

const nextPageUrl = computed(() => {
  if (props.meta.current_page === props.meta.last_page) return null
  
  const currentParams = new URLSearchParams(window.location.search)
  currentParams.set('page', String(props.meta.current_page + 1))
  currentParams.set('per_page', String(props.meta.per_page))
  
  return window.location.pathname + '?' + currentParams.toString()
})

const lastPageUrl = computed(() => {
  if (props.meta.current_page === props.meta.last_page) return null
  
  const currentParams = new URLSearchParams(window.location.search)
  currentParams.set('page', String(props.meta.last_page))
  currentParams.set('per_page', String(props.meta.per_page))
  
  return window.location.pathname + '?' + currentParams.toString()
})

const changePerPage = (perPage: any) => {
  if (!perPage) return
  const params = new URLSearchParams(window.location.search)
  params.set('page', '1')
  params.set('per_page', String(perPage))
  router.get(window.location.pathname + '?' + params.toString(), {}, {
    preserveState: true,
    preserveScroll: true
  })
}
</script>
