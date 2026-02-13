<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\ResourceController;
use Callcocam\LaravelRaptor\Support\Pages\Index;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends ResourceController
{
    public function getPages(): array
    {
        return [
            'index' => Index::route(config('raptor.controllers.dashboard.index.route', '/dashboard'))
                ->label(config('raptor.controllers.dashboard.index.label', __('Dashboard')))
                ->name(config('raptor.controllers.dashboard.index.name', 'dashboard.index'))
                ->icon(config('raptor.controllers.dashboard.index.icon', 'Home'))
                ->order(config('raptor.controllers.dashboard.index.order', 1))
                ->middlewares(config('raptor.controllers.dashboard.index.middlewares', ['auth', 'verified'])),
        ];
    }

    public function index(Request $request)
    {
        return Inertia::render(sprintf('admin/%s/dashboard', $this->resourcePath()), [
            'message' => 'Welcome to Laravel Raptor!',
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getResourceLabel(),
            'resourcePluralLabel' => $this->getResourcePluralLabel(),
            'breadcrumbs' => $this->breadcrumbs(),
        ]);
    }

    /**
     * Define o resource path para as views
     */
    protected function resourcePath(): ?string
    {
        return 'landlord';
    }
}
