<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner'
import { Head, usePage } from '@inertiajs/vue3';
import { computed, watch, nextTick } from 'vue';
import { toast } from 'vue-sonner'
import type { AppPageProps } from '@/types';

interface Props {
    title?: string;
    message?: string;
    maxWidth?: string;
}

const props = withDefaults(defineProps<Props>(), {
    maxWidth: '7xl',
    title: 'Dashboard'
});

const page = usePage<AppPageProps>();

// Título da página
const pageTitle = computed(() => props.title || 'Dashboard');

// Classes do container baseado na largura máxima
const containerClasses = computed(() => {
    const baseClasses = 'mx-auto w-full';
    const widthClass = {
        'sm': 'max-w-sm',
        'md': 'max-w-md',
        'lg': 'max-w-lg',
        'xl': 'max-w-xl',
        '2xl': 'max-w-2xl',
        '3xl': 'max-w-3xl',
        '4xl': 'max-w-4xl',
        '5xl': 'max-w-5xl',
        '6xl': 'max-w-6xl',
        '7xl': 'max-w-7xl',
        'full': 'max-w-full',
    }[props.maxWidth] || 'max-w-7xl';
    
    return `${baseClasses} ${widthClass}`;
});

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

        // Limpa as flash messages após mostrá-las
        if (flash && Object.keys(flash).length > 0) {
            nextTick(() => {
                page.props.flash = {};
            });
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
  <Head :title="pageTitle" />

  <div class="min-h-screen bg-background">
    <Toaster />
    
    <div :class="containerClasses">
      <slot>
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
          <slot name="content">
            <!-- Conteúdo padrão se nenhum slot for fornecido -->
            <div class="text-muted-foreground">
              {{ message }}
            </div>
          </slot>
        </div>
      </slot>
    </div>
  </div>
</template>
