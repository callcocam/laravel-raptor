<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Policies;

use Callcocam\LaravelRaptor\Models\Auth\User;
use Callcocam\LaravelRaptor\Models\Permission;

class PermissionPolicy
{
    protected $permission = 'permissions';

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(sprintf('%s.%s.view', request()->getContext(), $this->permission));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->can(sprintf('%s.%s.view', request()->getContext(), $this->permission));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(sprintf('%s.%s.create', request()->getContext(), $this->permission));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->can(sprintf('%s.%s.update', request()->getContext(), $this->permission));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->can(sprintf('%s.%s.delete', request()->getContext(), $this->permission));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Permission $permission): bool
    {
        return $user->can(sprintf('%s.%s.restore', request()->getContext(), $this->permission));
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Permission $permission): bool
    {
        return $user->can(sprintf('%s.%s.force-delete', request()->getContext(), $this->permission));
    }
}
