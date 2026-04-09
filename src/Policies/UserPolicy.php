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

    public function create(Authorizable $user): bool
    {
        if (! parent::create($user)) {
            return false;
        }

        if (! app()->bound('current.tenant') || ! class_exists(\App\Services\TenantLimitService::class)) {
            return true;
        }

        $tenant = app('current.tenant');
        $userModel = config('raptor.shinobi.models.user', \App\Models\User::class);
        $count = $userModel::where('tenant_id', $tenant->id)->withoutTrashed()->count();

        return ! app(\App\Services\TenantLimitService::class)->hasReachedLimit('max_users', $count);
    }

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
