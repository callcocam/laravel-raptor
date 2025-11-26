<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

trait HasBreadcrumbs
{
    /**
     * Monta breadcrumbs automaticamente baseado nas rotas pai
     *
     * O método analisa o nome da rota atual e constrói os breadcrumbs automaticamente.
     *
     * Exemplo automático: Para rota "landlord.users.edit" retorna:
     * [
     *   ['label' => 'Dashboard', 'url' => route('landlord.dashboard')],
     *   ['label' => 'Users', 'url' => route('landlord.users.index')],
     *   ['label' => 'Edit', 'url' => null],
     * ]
     *
     * Para personalizar, sobrescreva este método no seu controller:
     *
     * protected function breadcrumbs(): array
     * {
     *     return [
     *         ['label' => 'Home', 'url' => route('landlord.dashboard')],
     *         ['label' => 'Custom Label', 'url' => route('custom.route')],
     *         ['label' => 'Current Page', 'url' => null],
     *     ];
     * }
     *
     * @return array<int, array{label: string, url: string|null}>
     */
    protected function breadcrumbs(): array
    {
        $currentRoute = request()->route();

        if (! $currentRoute) {
            return [];
        }

        $routeName = $currentRoute->getName();

        if (! $routeName) {
            return [];
        }

        // Quebra o nome da rota em partes (ex: landlord.users.edit => ['landlord', 'users', 'edit'])
        $parts = explode('.', $routeName);
        $breadcrumbs = [];

        // Se tiver apenas uma parte, não há breadcrumbs
        if (count($parts) <= 1) {
            return [];
        }

        // Primeiro breadcrumb: Dashboard (contexto: landlord ou tenant)
        $context = $parts[0]; // 'landlord' ou 'tenant'
        $breadcrumbs[] = [
            'label' => __('Dashboard'),
            'url' => $this->getBreadcrumbUrl($context.'.dashboard'),
        ];

        // Breadcrumbs intermediários: recursos
        for ($i = 1; $i < count($parts); $i++) {
            $part = $parts[$i];

            // Ignora a última parte se for uma action (index, create, edit, show, etc)
            if ($i === count($parts) - 1 && in_array($part, ['index', 'create', 'edit', 'show', 'store', 'update', 'destroy'])) {
                // Adiciona a action como último breadcrumb sem link
                $breadcrumbs[] = [
                    'label' => $this->formatBreadcrumbLabel($part),
                    'url' => null,
                ];
                break;
            }

            // Constrói o nome da rota para o breadcrumb
            $routePath = implode('.', array_slice($parts, 0, $i + 1));

            // Se não for a última parte, adiciona ".index" para link de listagem
            if ($i < count($parts) - 1) {
                $routePath .= '.index';
            }

            $breadcrumbs[] = [
                'label' => $this->formatBreadcrumbLabel($part),
                'url' => $this->getBreadcrumbUrl($routePath),
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Formata o label do breadcrumb
     */
    protected function formatBreadcrumbLabel(string $label): string
    {
        // Mapeia actions comuns para labels mais amigáveis
        $actionLabels = [
            'index' => __('List'),
            'create' => __('Create'),
            'edit' => __('Edit'),
            'show' => __('View'),
        ];

        if (isset($actionLabels[$label])) {
            return $actionLabels[$label];
        }

        // Transforma snake_case ou kebab-case em Title Case
        return __(str($label)->replace(['-', '_'], ' ')->title()->toString());
    }

    /**
     * Retorna a URL do breadcrumb, ou null se a rota não existir
     */
    protected function getBreadcrumbUrl(string $routeName): ?string
    {
        try {
            return route($routeName);
        } catch (\Exception) {
            return null;
        }
    }
}
