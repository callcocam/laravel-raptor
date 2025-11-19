<script setup lang="ts">
import AppShell from '@/components/layout/AppShell.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Props {
    resourceName: string;
    resourcePluralName: string;
    resourceLabel: string;
    resourcePluralLabel: string;
    maxWidth?: string;
    breadcrumbs: Array<{ label: string; href?: string }>;
    form: {
        columns: any[];
    };
}

const props = withDefaults(defineProps<Props>(), {
    maxWidth: '7xl',
});
</script>

<template>
    <AppShell
        variant="sidebar"
        :title="`Criar ${resourceLabel}`"
        :breadcrumbs="breadcrumbs"
        :max-width="maxWidth"
    >
        <Card>
            <CardHeader>
                <CardTitle>Criar {{ resourceLabel }}</CardTitle>
                <CardDescription>
                    Preencha os campos abaixo para criar um novo {{ resourceLabel.toLowerCase() }}.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Form will be rendered here -->
                <p class="text-sm text-muted-foreground">Formulário em desenvolvimento...</p>
            </CardContent>
        </Card>
    </AppShell>
</template>
