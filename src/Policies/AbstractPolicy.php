<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Policies;

use App\Models\User;
use Callcocam\LaravelRaptor\Models\AbstractModel;

abstract class AbstractPolicy
{
    protected ?string $permission = null;

    protected ?string $context = null;

    protected function generatePermissionName(string $action): array
    {
        return [
            sprintf('%s.%s.%s', $this->context ?? request()->getContext(), $this->permission, $action),
            sprintf('%s.%s', $this->permission, $action),
            sprintf('admin.%s.%s', $this->permission, $action),
        ];
    }

    /**
     * Verifica se o usuário tem qualquer uma das permissões possíveis
     */
    protected function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('index'));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AbstractModel $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('view'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('create'));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AbstractModel $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('update'))
            || $this->hasAnyPermission($user, $this->generatePermissionName('edit'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AbstractModel $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('delete'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AbstractModel $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('restore'));
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AbstractModel $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('forceDelete'));
    }
}
