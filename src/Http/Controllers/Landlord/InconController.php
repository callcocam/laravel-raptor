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

class InconController extends ResourceController
{
    public function getPages(): array
    {
        return [
            'index' => Index::route(config('raptor.controllers.icons.index.route', '/icons'))
                ->label(config('raptor.controllers.icons.index.label', __('Icons')))
                ->name(config('raptor.controllers.icons.index.name', 'icons.index'))
                ->icon(config('raptor.controllers.icons.index.icon', 'BrickWallFire'))
                ->order(config('raptor.controllers.icons.index.order', 1))
                ->middlewares(config('raptor.controllers.icons.index.middlewares', ['auth', 'verified'])),
        ];
    }

    public function index(Request $request)
    {
        return Inertia::render(sprintf('admin/%s/icons', $this->resourcePath()), [
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
