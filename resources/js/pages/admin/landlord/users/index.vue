<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useBreadcrumbs, type BackendBreadcrumb } from '@/composables/useBreadcrumbs';
import { Head } from '@inertiajs/vue3';

interface Props {
    message?: string;
    resourceName: string;
    resourcePluralName: string;
    resourceLabel: string;
    resourcePluralLabel: string;
    breadcrumbs?: BackendBreadcrumb[];
}

const props = defineProps<Props>();

// Os breadcrumbs vêm automaticamente do backend via HasBreadcrumbs trait
// Para rota "landlord.users.index", o backend gera:
// [
//   { label: 'Dashboard', url: route('landlord.dashboard') },
//   { label: 'Users', url: null }
// ]
const breadcrumbs = useBreadcrumbs(() => props.breadcrumbs);
</script>

<template>
  <Head :title="resourcePluralLabel" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ resourcePluralLabel }}</h1>
      </div>

      <div class="rounded-xl border p-4">
        <!-- Seu conteúdo aqui -->
        <p class="text-muted-foreground">Lista de {{ resourcePluralLabel.toLowerCase() }}</p>
      </div>
    </div>
  </AppLayout>
</template>
