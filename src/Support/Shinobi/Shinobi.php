<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Shinobi;

use Callcocam\LaravelRaptor\Support\Shinobi\Tactics\AssignRoleTo;
use Callcocam\LaravelRaptor\Support\Shinobi\Tactics\GivePermissionTo;
use Callcocam\LaravelRaptor\Support\Shinobi\Tactics\RevokePermissionFrom;

class Shinobi
{
    /**
     * Fetch an instance of the Role model.
     *
     * @return Role
     */
    public function role()
    {
        return app()->make(config('raptor.shinobi.models.role'));
    }

    /**
     * Fetch an instance of the Permission model.
     *
     * @return Permission
     */
    public function permission()
    {
        return app()->make(config('raptor.shinobi.models.permission'));
    }

    /**
     * Assign roles to a user.
     *
     * @param  string|array  $roles
     * @return \Callcocam\LaravelRaptor\Support\Shinobi\Tactic\AssignRoleTo
     */
    public function assign($roles): AssignRoleTo
    {
        return new AssignRoleTo($roles);
    }

    /**
     * Give permissions to a user or role
     *
     * @param  string|array  $permissions
     * @return \Callcocam\LaravelRaptor\Support\Shinobi\Tactic\GivePermissionTo
     */
    public function give($permissions): GivePermissionTo
    {
        return new GivePermissionTo($permissions);
    }

    /**
     * Revoke permissions from a user or role
     *
     * @param  string|array  $permissions
     * @return \Callcocam\LaravelRaptor\Support\Shinobi\Tactic\RevokePermissionFrom
     */
    public function revoke($permissions): RevokePermissionFrom
    {
        return new RevokePermissionFrom($permissions);
    }
}
