<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner'
import FlashMessages from '~/components/FlashMessages.vue';
import { useLayout } from '~/composables/useLayout';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    title?: string;
    message?: string;
    maxWidth?: string;
}

const props = withDefaults(defineProps<Props>(), {
    maxWidth: '7xl',
    title: 'Dashboard'
});

// Título da página
const pageTitle = computed(() => props.title || 'Dashboard');

// Configuração de layout com largura máxima
const { containerClasses } = useLayout(props.maxWidth);
</script>

<template>
  <Head :title="pageTitle" />

  <div class="min-h-screen bg-background">
    <FlashMessages />
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
