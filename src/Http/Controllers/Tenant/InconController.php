<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Tenant;

use Callcocam\LaravelRaptor\Http\Controllers\ResourceController;
use Callcocam\LaravelRaptor\Support\Pages\Index;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InconController extends ResourceController
{

    public function getPages(): array
    {
        return [
            'index' => Index::route('/icons')
                ->label(config('raptor.controllers.icons.label', __('Icons')))
                ->name(config('raptor.controllers.icons.name', 'icons.index'))
                ->icon(config('raptor.controllers.icons.icon', 'BrickWallFire'))
                ->order(config('raptor.controllers.icons.order', 1))
                ->group(config('raptor.controllers.icons.group', 'Operacional'))
                ->groupCollapsible(config('raptor.controllers.icons.groupCollapsible', true))
                ->visible(function () {
                    if (auth()->user()) {
                        return auth()->user()->isAdmin();
                    }
                    return false;
                })
                ->middlewares(['auth', 'verified']),
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
