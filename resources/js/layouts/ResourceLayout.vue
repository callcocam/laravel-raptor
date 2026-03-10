<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import RaptorLayout from '~/layouts/RaptorLayout.vue';
import { useBreadcrumbs, type BackendBreadcrumb } from '~/composables/useBreadcrumbs';
import { useLayout } from '~/composables/useLayout';
import { dashboard } from '@/routes';

interface Props {
    title?: string;
    /** Subtítulo exibido abaixo do título da página */
    message?: string;
    resourceName?: string;
    resourcePluralName?: string;
    resourceLabel?: string;
    resourcePluralLabel?: string;
    maxWidth?: string;
    breadcrumbs?: BackendBreadcrumb[];
    actionName?: string;
    /**
     * Modo tela cheia: o conteúdo ocupa exatamente o espaço restante da viewport.
     * Ideal para Kanban, editores de planograma, painéis com scroll por coluna.
     * Quando ativo, maxWidth é ignorado — o container usa h-full sem centralização.
     */
    fullHeight?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    maxWidth: '7xl',
    title: 'Dashboard',
    fullHeight: false,
});

const breadcrumbs = useBreadcrumbs(
    () => props.breadcrumbs,
    [{ title: 'Dashboard', href: dashboard().url }],
);

const { containerClasses } = useLayout(props.maxWidth);

const pageTitle = computed(() => props.title || props.resourcePluralLabel || 'Dashboard');
const pageMessage = computed(() => props.message || props.resourceLabel || '');

// Em modo fullHeight: container ocupa toda a altura disponível sem max-width
// Em modo normal: usa containerClasses (max-w-* mx-auto w-full)
const wrapperClasses = computed(() =>
    props.fullHeight ? 'flex h-full flex-1 flex-col' : containerClasses.value,
);
</script>

<template>
    <Head :title="pageTitle" />

    <RaptorLayout
        :breadcrumbs="breadcrumbs"
        :title="pageTitle"
        :message="pageMessage"
        :show-page-header="!$slots.header"
        :full-height="fullHeight"
    >
        <!-- Slot header-actions: repassa para o RaptorLayout -->
        <template v-if="$slots['header-actions']" #header-actions>
            <slot name="header-actions" />
        </template>

        <!--
            Slot "header" (retrocompatibilidade com IndexPage.vue e demais páginas)
            Renderizado diretamente dentro do RaptorLayout como slot "header"
        -->
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <!-- Conteúdo principal -->
        <div :class="wrapperClasses">
            <slot>
                <div
                    :class="[
                        'flex flex-1 flex-col gap-4 overflow-x-auto p-4',
                        fullHeight ? 'h-full min-h-0' : '',
                    ]"
                >
                    <slot name="content">
                        <div v-if="pageMessage" class="text-sm text-muted-foreground">
                            {{ pageMessage }}
                        </div>
                    </slot>
                </div>
            </slot>
        </div>
    </RaptorLayout>
</template>
