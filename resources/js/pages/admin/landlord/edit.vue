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
    model: any;
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
        :title="`Editar ${resourceLabel}`"
        :breadcrumbs="breadcrumbs"
        :max-width="maxWidth"
    >
        <Card>
            <CardHeader>
                <CardTitle>Editar {{ resourceLabel }}</CardTitle>
                <CardDescription>
                    Atualize os campos abaixo para editar o {{ resourceLabel.toLowerCase() }}.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Form will be rendered here -->
                <p class="text-sm text-muted-foreground">Formulário em desenvolvimento...</p>
                <pre class="mt-4 p-4 bg-muted rounded-md text-xs">{{ model }}</pre>
            </CardContent>
        </Card>
    </AppShell>
</template>
