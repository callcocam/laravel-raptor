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
    infolist: any[];
}

const props = withDefaults(defineProps<Props>(), {
    maxWidth: '7xl',
});
</script>

<template>
    <AppShell
        variant="sidebar"
        :title="`Visualizar ${resourceLabel}`"
        :breadcrumbs="breadcrumbs"
        :max-width="maxWidth"
    >
        <Card>
            <CardHeader>
                <CardTitle>{{ resourceLabel }}</CardTitle>
                <CardDescription>
                    Detalhes do {{ resourceLabel.toLowerCase() }}.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Infolist will be rendered here -->
                <pre class="p-4 bg-muted rounded-md text-xs">{{ model }}</pre>
            </CardContent>
        </Card>
    </AppShell>
</template>
