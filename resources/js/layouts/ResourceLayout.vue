<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Toaster } from '@/components/ui/sonner'
import { useBreadcrumbs, type BackendBreadcrumb } from '@/composables/useBreadcrumbs';
import { useLayout } from '@/composables/useLayout';
import { dashboard } from '@/routes';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner'
import type { AppPageProps } from '@/types';

interface Props {
    title?: string;
    message?: string;
    resourceName?: string;
    resourcePluralName?: string;
    resourceLabel?: string;
    resourcePluralLabel?: string;
    maxWidth?: string;
    breadcrumbs?: BackendBreadcrumb[];
}

const props = withDefaults(defineProps<Props>(), {
    maxWidth: '7xl',
    title: 'Dashboard'
});

const page = usePage<AppPageProps>();

// Mapeia breadcrumbs do backend para o formato do frontend
const breadcrumbs = useBreadcrumbs(
    () => props.breadcrumbs,
    [{ title: 'Dashboard', href: dashboard().url }]
);

// Configuração de layout com largura máxima do backend
const { containerClasses } = useLayout(props.maxWidth);

// Título da página (usa title prop ou resourcePluralLabel como fallback)
const pageTitle = computed(() => props.title || props.resourcePluralLabel || 'Dashboard'); 
// Watch para mensagens flash e exibir toasts
watch(
    () => page.props.flash,
    (flash) => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
        if (flash.warning) {
            toast.warning(flash.warning);
        }
        if (flash.info) {
            toast.info(flash.info);
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
  <Head :title="pageTitle" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <slot name="header"> </slot>
    <Toaster />
    <div :class="containerClasses">
      <slot>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
          <slot name="content">
            <!-- Conteúdo padrão se nenhum slot for fornecido -->
            <div class="text-muted-foreground">
              {{ message || resourceLabel }}
            </div>
          </slot>
        </div>
      </slot>
    </div>
  </AppLayout>
</template>
