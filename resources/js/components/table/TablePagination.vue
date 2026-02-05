<template>
  <div v-if="meta.last_page > 1" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 px-2 py-4 border-t">
    <!-- Info de registros - oculta no mobile -->
    <div class="hidden sm:block text-sm text-muted-foreground">
      Mostrando <span class="font-medium">{{ meta.from }}</span> até
      <span class="font-medium">{{ meta.to }}</span> de
      <span class="font-medium">{{ meta.total }}</span> registros
    </div>

    <!-- Paginação responsiva -->
    <div class="flex items-center gap-1 w-full sm:w-auto justify-center">
      <!-- Mobile: apenas botões de navegação -->
      <div class="flex sm:hidden items-center gap-1">
        <!-- Primeiro -->
        <Link
          :href="firstPageUrl || '#'"
          :class="[
            'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors',
            'h-7 px-2',
            'hover:bg-accent hover:text-accent-foreground',
            'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
            {
              'pointer-events-none opacity-50': !firstPageUrl
            }
          ]"
          :preserve-state="true"
          :preserve-scroll="true"
        >
          ‹‹
        </Link>
        
        <!-- Anterior -->
        <Link
          :href="prevPageUrl || '#'"
          :class="[
            'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors',
            'h-7 px-2',
            'hover:bg-accent hover:text-accent-foreground',
            'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
            {
              'pointer-events-none opacity-50': !prevPageUrl
            }
          ]"
          :preserve-state="true"
          :preserve-scroll="true"
        >
          ‹
        </Link>

        <!-- Indicador de página atual -->
        <span class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 px-3 bg-primary text-primary-foreground">
          {{ meta.current_page }} / {{ meta.last_page }}
        </span>

        <!-- Próximo -->
        <Link
          :href="nextPageUrl || '#'"
          :class="[
            'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors',
            'h-7 px-2',
            'hover:bg-accent hover:text-accent-foreground',
            'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
            {
              'pointer-events-none opacity-50': !nextPageUrl
            }
          ]"
          :preserve-state="true"
          :preserve-scroll="true"
        >
          ›
        </Link>

        <!-- Último -->
        <Link
          :href="lastPageUrl || '#'"
          :class="[
            'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors',
            'h-7 px-2',
            'hover:bg-accent hover:text-accent-foreground',
            'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
            {
              'pointer-events-none opacity-50': !lastPageUrl
            }
          ]"
          :preserve-state="true"
          :preserve-scroll="true"
        >
          ››
        </Link>
      </div>

      <!-- Desktop: todos os links de paginação -->
      <div class="hidden sm:flex items-center gap-1">
        <Link
          v-for="(link, index) in paginationLinks"
          :key="index"
          :href="link.url || '#'"
          :class="[
            'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors truncate',
            'h-7 px-2 min-w-[36px]',
            'hover:bg-accent hover:text-accent-foreground',
            'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
            {
              'bg-primary text-primary-foreground hover:bg-primary/90': link.active,
              'pointer-events-none opacity-50': !link.url,
              'text-muted-foreground': !link.active && link.label === '...',
              ' btn-gradient': link.url && !link.active
            }
          ]"
          :preserve-state="true"
          :preserve-scroll="true"
          v-html="link.label"
        />
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
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
