<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Tenant;

use Callcocam\LaravelRaptor\Http\Controllers\TenantController;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithRequests;

class RoleController extends TenantController
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
        return 'tenant';
    }
}
