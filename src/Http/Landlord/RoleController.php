<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithRequests;

class RoleController extends LandlordController
{
    use WithRequests;

    /**
     * Define o model que será usado pelo controller
     */
    protected function model(): string
    {
        return config('raptor.models.role', \Callcocam\LaravelRaptor\Support\Shinobi\Models\Role::class);
    }

    /**
     * Define o resource path para as views
     */
    protected function resourcePath(): string
    {
        return 'landlord';
    }
}
