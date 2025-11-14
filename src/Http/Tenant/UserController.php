<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Tenant;

use Callcocam\LaravelRaptor\Http\Controllers\TenantController;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithRequests;

class UserController extends TenantController
{
    use WithRequests;

    /**
     * Define o model que será usado pelo controller
     */
    protected function model(): string
    {
        return config('raptor.models.user', \Callcocam\LaravelRaptor\Models\Auth\User::class);
    }
    /**
     * Define o resource path para as views
     */
    protected function resourcePath(): string
    {
        return 'tenant';
    }
}
