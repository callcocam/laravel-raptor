<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Tenant;

use Callcocam\LaravelRaptor\Http\Controllers\TenantController;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithRequests;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;

class ImageController extends TenantController
{
    use WithRequests;

    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.models.image', \Callcocam\LaravelRaptor\Models\Image::class);
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        return $table;
    }

    /**
     * Define o resource path para as views
     */
    protected function resourcePath(): string
    {
        return 'tenant';
    }
}
