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
                ->label('Icons')
                ->name('icons.index')
                ->icon('BrickWallFire')
                ->order(1)
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
