import type { BreadcrumbItem } from '@/types';
import { computed, type ComputedRef } from 'vue';

/**
 * Estrutura de breadcrumb vinda do backend Laravel
 */
export interface BackendBreadcrumb {
    label: string;
    url: string | null;
}

/**
 * Composable para mapear breadcrumbs do backend para o formato do frontend
 *
 * @example
 * ```vue
 * <script setup lang="ts">
 * import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
 *
 * interface Props {
 *     breadcrumbs?: BackendBreadcrumb[];
 * }
 *
 * const props = defineProps<Props>();
 * const breadcrumbs = useBreadcrumbs(() => props.breadcrumbs);
 * </script>
 * ```
 */
export function useBreadcrumbs(
    breadcrumbsGetter: () => BackendBreadcrumb[] | undefined,
    fallback?: BreadcrumbItem[]
): ComputedRef<BreadcrumbItem[]> {
    return computed(() => {
        const backendBreadcrumbs = breadcrumbsGetter();

        if (!backendBreadcrumbs || backendBreadcrumbs.length === 0) {
            return fallback || [];
        }

        return backendBreadcrumbs.map((breadcrumb) => ({
            title: breadcrumb.label,
            href: breadcrumb.url || '#',
        }));
    });
}
