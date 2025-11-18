<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useBreadcrumbs, type BackendBreadcrumb } from '@/composables/useBreadcrumbs';
import { Head } from '@inertiajs/vue3';

interface Props {
    resourceName: string;
    resourcePluralName: string;
    resourceLabel: string;
    resourcePluralLabel: string;
    breadcrumbs?: BackendBreadcrumb[];
}

const props = defineProps<Props>();

// Os breadcrumbs vêm automaticamente do backend via HasBreadcrumbs trait
// Para rota "landlord.users.edit", o backend gera:
// [
//   { label: 'Dashboard', url: route('landlord.dashboard') },
//   { label: 'Users', url: route('landlord.users.index') },
//   { label: 'Edit', url: null }
// ]
const breadcrumbs = useBreadcrumbs(() => props.breadcrumbs);
</script>

<template>
  <Head :title="`Edit ${resourceLabel}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Edit {{ resourceLabel }}</h1>
      </div>

      <div class="rounded-xl border p-4">
        <!-- Seu formulário de edição aqui -->
        <p class="text-muted-foreground">Formulário de edição de {{ resourceLabel.toLowerCase() }}</p>
      </div>
    </div>
  </AppLayout>
</template>
