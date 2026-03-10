<template>
  <main class="px-6 py-2 w-full border-t border-slate-200">
    <!-- Breadcrumbs & Actions -->
    <div class="mb-6 mt-4 flex flex-col items-center justify-between gap-4 md:flex-row">
      <slot name="tabs" /> 
    </div>
    <!-- Search and Filters Section -->
    <div class="mb-8 flex flex-wrap items-center gap-3 border-b pb-4 w-full">
      <TableFilters v-if="table.filters.value.length || table.searchable.value" :filters="table.filters.value"
        :searchable="table.searchable.value" @apply="table.filter" @clear="table.reset" />
    </div>
    <!-- Planogram List Cards -->
    <div class="space-y-6" v-if="table.records.value.length">
      <slot name="content" />
    </div>
    <!-- Pagination -->
    <TablePagination v-if="table.meta.value.total > 0" :meta="table.meta.value" @page-change="table.page"
      @per-page-change="table.perPage" />
  </main>
</template>

<script setup lang="ts">  
import TablePagination from '~/components/table/TablePagination.vue';  
import TableFilters from '~/components/filters/TableFilters.vue';

 defineProps<{
  table: any;
 }>();

</script>
