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

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if (is_null($this->permission)) {
            return false;
        }
        return $user->can(sprintf('%s.%s.index', $this->context ?? request()->getContext(), $this->permission));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AbstractModel $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }
        return $user->can(sprintf('%s.%s.view', $this->context ?? request()->getContext(), $this->permission));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (is_null($this->permission)) {
            return false;
        }
        return $user->can(sprintf('%s.%s.create', $this->context ??  request()->getContext(), $this->permission));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AbstractModel $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }
        return $user->can([
            sprintf('%s.%s.edit', $this->context ?? request()->getContext(), $this->permission),
            sprintf('%s.%s.update', $this->context ?? request()->getContext(), $this->permission)
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AbstractModel $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }
        return $user->can(sprintf('%s.%s.delete', $this->context ?? request()->getContext(), $this->permission));
    }
    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AbstractModel $model): bool
    {
        if (is_null($this->permission)) {
            return false;
        }
        return $user->can(sprintf('%s.%s.restore', $this->context ?? request()->getContext(), $this->permission));
    }
}
