<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;

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
    protected function hasAnyPermission(Authorizable $user, array $permissions): bool
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
    public function viewAny(Authorizable $user): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('index'));
    }

    /**
     * Alias para viewAny. Permite usar a habilidade index de forma consistente.
     */
    public function index(Authorizable $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Authorizable $user, object $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('view'));
    }

    /**
     * Alias para view. Permite usar a habilidade show de forma consistente.
     */
    public function show(Authorizable $user, object $model): bool
    {
        return $this->view($user, $model);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Authorizable $user): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, array_merge(
            $this->generatePermissionName('create'),
            $this->generatePermissionName('execute')
        ));
    }

    /**
     * Alias para create. Permite usar a habilidade store de forma consistente.
     */
    public function store(Authorizable $user): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Authorizable $user, object $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('update'))
            || $this->hasAnyPermission($user, $this->generatePermissionName('edit'));
    }

    /**
     * Alias para update. Permite usar a habilidade edit de forma consistente.
     */
    public function edit(Authorizable $user, object $model): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Authorizable $user, object $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('delete'))
            || $this->hasAnyPermission($user, $this->generatePermissionName('destroy'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Authorizable $user, object $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('restore'));
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Authorizable $user, object $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }

        return $this->hasAnyPermission($user, $this->generatePermissionName('forceDelete'))
            || $this->hasAnyPermission($user, $this->generatePermissionName('force-delete'));
    }
}
