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
  </AppLayout>
</template>
