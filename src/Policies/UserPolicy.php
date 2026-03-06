<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Policies;

use Callcocam\LaravelRaptor\Models\Auth\User;
use Illuminate\Contracts\Auth\Access\Authorizable;

class UserPolicy extends AbstractPolicy
{
    protected ?string $permission = 'users';

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Authorizable $user, object $model): bool
    {
        if ($model instanceof User && $user instanceof User && $user->id === $model->id) {
            return false;
        }

        return parent::forceDelete($user, $model);
    }
}
