<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Toaster } from '@/components/ui/sonner'
import FlashMessages from '~/components/FlashMessages.vue';
import { useBreadcrumbs, type BackendBreadcrumb } from '~/composables/useBreadcrumbs';
import { useLayout } from '~/composables/useLayout';
import { dashboard } from '@/routes';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    title?: string;
    /** Descrição/subtítulo exibido abaixo do título da página */
    message?: string;
    resourceName?: string;
    resourcePluralName?: string;
    resourceLabel?: string;
    resourcePluralLabel?: string;
    maxWidth?: string;
    breadcrumbs?: BackendBreadcrumb[];
    actionName?: string;
}

const props = withDefaults(defineProps<Props>(), {
    maxWidth: '7xl',
    title: 'Dashboard'
});

// Mapeia breadcrumbs do backend para o formato do frontend
const breadcrumbs = useBreadcrumbs(
    () => props.breadcrumbs,
    [{ title: 'Dashboard', href: dashboard().url }]
);

// Configuração de layout com largura máxima do backend
const { containerClasses } = useLayout(props.maxWidth);

// Título da página (usa title prop ou resourcePluralLabel como fallback)
const pageTitle = computed(() => props.title || props.resourcePluralLabel || 'Dashboard');
</script>

<template>
  <Head :title="pageTitle" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <slot name="header"> </slot>
    <FlashMessages />
    <Toaster />
    <div :class="containerClasses">
      <slot>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
          <slot name="content">
            <!-- Conteúdo padrão se nenhum slot for fornecido (descrição só aparece se existir) -->
            <div v-if="message || resourceLabel" class="text-muted-foreground">
              {{ message || resourceLabel }}
            </div>
          </slot>
        </div>
      </slot>
    </div>
    <div class="row-span-2 md:row-span-1 xl:row-span-2"></div>
    <div class="row-span-3 md:row-span-2 xl:row-span-3"></div>
    <div class="row-span-4 md:row-span-3 xl:row-span-4"></div>
    <div class="row-span-5 md:row-span-4 xl:row-span-5"></div>
    <div class="row-span-6 md:row-span-5 xl:row-span-6"></div>
    <div class="row-span-7 md:row-span-5 xl:row-span-7"></div>
    <div class="row-span-8 md:row-span-6 xl:row-span-8"></div> 
  </AppLayout>
</template>
