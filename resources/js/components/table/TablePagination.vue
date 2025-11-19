<template>
  <div v-if="meta.last_page > 1" class="flex items-center justify-between px-2 py-4 border-t">
    <div class="text-sm text-muted-foreground">
      Mostrando <span class="font-medium">{{ meta.from }}</span> até
      <span class="font-medium">{{ meta.to }}</span> de
      <span class="font-medium">{{ meta.total }}</span> registros
    </div>

    <div class="flex items-center gap-1">
      <Link
        v-for="(link, index) in paginationLinks"
        :key="index"
        :href="link.url || '#'"
        :class="[
          'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors',
          'h-9 px-3 min-w-[36px]',
          'hover:bg-accent hover:text-accent-foreground',
          'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
          {
            'bg-primary text-primary-foreground hover:bg-primary/90': link.active,
            'pointer-events-none opacity-50': !link.url,
            'text-muted-foreground': !link.active && link.label === '...'
          }
        ]"
        :preserve-state="true"
        :preserve-scroll="true"
        v-html="link.label"
      />
    </div>

    <div class="flex items-center gap-2">
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
