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
            'index' => Index::route('/dashboard')
                ->label('Dashboard')
                ->name('dashboard.index')
                ->icon('Home')
                ->order(1)
                ->middlewares(['auth', 'verified']),
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
