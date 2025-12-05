<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch, nextTick } from 'vue';
import { toast } from 'vue-sonner';
import type { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();

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
        // Isso evita que elas apareçam novamente em navegações subsequentes
        if (flash && Object.keys(flash).length > 0) {
            // Usa nextTick para garantir que o toast foi mostrado antes de limpar
            nextTick(() => {
                page.props.flash = {};
            });
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
  <!-- Este componente não renderiza nada, apenas gerencia flash messages -->
</template>
