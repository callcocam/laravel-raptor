<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Toaster } from '@/components/ui/sonner';
import AppSidebar from '@/components/AppSidebar.vue';
import FlashMessages from '~/components/FlashMessages.vue';
import RaptorHeader from '~/components/RaptorHeader.vue';
import { SidebarProvider, SidebarInset } from '~/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

interface Props {
    title?: string;
    message?: string;
    resourceLabel?: string;
    resourcePluralLabel?: string;
    /** Breadcrumbs já mapeados (BreadcrumbItem[]) — o mapeamento acontece no ResourceLayout */
    breadcrumbs?: BreadcrumbItem[];
    /** Mostra o cabeçalho de página (título + subtítulo) */
    showPageHeader?: boolean;
    /**
     * Modo tela cheia: o layout ocupa exatamente 100svh.
     * Útil para Kanban, editores e painéis com scroll interno por coluna.
     * Quando ativo, o conteúdo deve usar h-full + overflow-hidden/auto internamente.
     */
    fullHeight?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Dashboard',
    showPageHeader: true,
    fullHeight: false,
});

const page = usePage();
const sidebarOpen = (page.props as any).sidebarOpen ?? true;

const pageTitle = computed(() => props.title || props.resourcePluralLabel || 'Dashboard');
const pageSubtitle = computed(() => props.message || props.resourceLabel || ''); 
</script>

<template>
    <Head :title="pageTitle" />

    <!-- Shell: SidebarProvider (pacote nativo, sem reka-ui) -->
    <SidebarProvider :default-open="sidebarOpen" :full-height="fullHeight">
        <!-- Sidebar -->
        <AppSidebar />

        <!-- Conteúdo principal -->
        <SidebarInset :class="fullHeight ? 'overflow-hidden' : ''">
            <!-- Header de navegação superior -->
            <RaptorHeader :breadcrumbs="props.breadcrumbs ?? []" />

            <!-- Flash messages e Toaster -->
            <FlashMessages />

            <!-- Cabeçalho da página (título + subtítulo + ações) -->
            <div
                v-if="showPageHeader && (pageTitle || pageSubtitle || $slots['header-actions'])"
                class="flex shrink-0 items-start justify-between gap-4 border-b border-border/60 bg-background px-4 py-3 sm:px-6"
            >
                <div class="min-w-0">
                    <h1 class="truncate text-xl font-bold tracking-tight text-foreground">
                        {{ pageTitle }}
                    </h1>
                    <p v-if="pageSubtitle" class="mt-0.5 truncate text-sm text-muted-foreground">
                        {{ pageSubtitle }}
                    </p>
                </div>

                <!-- Slot para ações do cabeçalho (ex: botões Exportar, Importar) -->
                <div v-if="$slots['header-actions']" class="flex shrink-0 items-center gap-2">
                    <slot name="header-actions" />
                </div>
            </div>

            <!-- Slot de cabeçalho personalizado (retrocompatibilidade) -->
            <slot name="header" />

            <!-- Conteúdo da página — sem max-width aqui; quem define é ResourceLayout ou a página -->
            <!--
                min-h-0: essencial em flex para evitar que o filho expanda além do pai
                overflow-hidden: junto com fullHeight, garante que o conteúdo não vaze
            -->
            <main
                :class="[
                    'flex flex-1 flex-col overflow-x-hidden',
                    fullHeight ? 'min-h-0 overflow-hidden' : '',
                ]"
            >
                <slot>
                    <slot name="content" />
                </slot>
            </main>
        </SidebarInset>
    </SidebarProvider>

    <!-- Notificações toast (fora do layout para z-index correto) -->
    <Toaster />

    <!--
        Divs invisíveis para forçar o Tailwind a incluir as classes de grid/row-span
        usadas dinamicamente pelo sistema de layout responsivo.
    -->
    <div class="hidden">
        <div class="row-span-2 md:row-span-1 xl:row-span-2" />
        <div class="row-span-3 md:row-span-2 xl:row-span-3" />
        <div class="row-span-4 md:row-span-3 xl:row-span-4" />
        <div class="row-span-5 md:row-span-4 xl:row-span-5" />
        <div class="row-span-6 md:row-span-5 xl:row-span-6" />
        <div class="row-span-7 md:row-span-5 xl:row-span-7" />
        <div class="row-span-8 md:row-span-6 xl:row-span-8" />
        <div class="col-span-1 gap-x-1 md:col-span-1 lg:col-span-1" />
        <div class="col-span-2 gap-x-2 md:col-span-2 lg:col-span-2" />
        <div class="col-span-3 gap-x-3 md:col-span-3 lg:col-span-3" />
        <div class="col-span-4 gap-x-4 md:col-span-4 lg:col-span-4" />
        <div class="col-span-5 gap-x-5 md:col-span-5 lg:col-span-5" />
        <div class="col-span-6 gap-x-6 md:col-span-6 lg:col-span-6" />
        <div class="col-span-7 gap-x-7 md:col-span-7 lg:col-span-7" />
        <div class="col-span-8 gap-x-8 md:col-span-8 lg:col-span-8" />
        <div class="col-span-9 md:col-span-9 lg:col-span-9" />
        <div class="col-span-10 md:col-span-10 lg:col-span-10" />
        <div class="col-span-11 md:col-span-11 lg:col-span-11" />
        <div class="col-span-12 md:col-span-12 lg:col-span-12" />
    </div>
</template>
