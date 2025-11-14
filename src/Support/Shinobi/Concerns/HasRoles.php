<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Shinobi\Concerns;

use Callcocam\LaravelRaptor\Models\Role as ModelsRole;
use Callcocam\LaravelRaptor\Support\Shinobi\Contracts\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasRoles
{
    /**
     * Users can have many roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(config('raptor.models.role', ModelsRole::class))->withTimestamps();
    }

    /**
     * Checks if the model has the given role assigned.
     *
     * @param  string  $role
     */
    public function hasRole($role): bool
    {
        $slug = Str::slug($role);

        return (bool) $this->roles->where('slug', $slug)->count();
    }

    /**
     * Checks if the model has any of the given roles assigned.
     *
     * @param  array  $roles
     */
    public function hasAnyRole(...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the model has all of the given roles assigned.
     *
     * @param  array  $roles
     */
    public function hasAllRoles(...$roles): bool
    {
        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function hasRoles(): bool
    {
        return (bool) $this->roles->count();
    }

    /**
     * Assign the specified roles to the model.
     *
     * @param  mixed  $roles,...
     */
    public function assignRoles(...$roles): self
    {
        $roles = Arr::flatten($roles);
        $roles = $this->getRoles($roles);

        if (! $roles) {
            return $this;
        }

        $this->roles()->syncWithoutDetaching($roles);

        return $this;
    }

    /**
     * Remove the specified roles from the model.
     *
     * @param  mixed  $roles,...
     */
    public function removeRoles(...$roles): self
    {
        $roles = Arr::flatten($roles);
        $roles = $this->getRoles($roles);

        $this->roles()->detach($roles);

        return $this;
    }

    /**
     * Sync the specified roles to the model.
     *
     * @param  mixed  $roles,...
     */
    public function syncRoles(...$roles): self
    {
        $roles = Arr::flatten($roles);
        $roles = $this->getRoles($roles);

        $this->roles()->sync($roles);

        return $this;
    }

    /**
     * Get the specified roles.
     *
     * @return Role
     */
    protected function getRoles(array $roles)
    {
        return array_map(function ($role) {
            $model = $this->getRoleModel();

            if ($role instanceof $model) {
                return $role->id;
            }

            $role = $model->where('slug', $role)->first();

            return $role->id;
        }, $roles);
    }

    public function hasPermissionRoleFlags()
    {
        if ($this->hasRoles()) {
            return $this->roles
                ->filter(function ($role) {
                    return $role->special;
                })->count() >= 1;
        }

        return false;
    }

    /**
     * Get the model instance responsible for permissions.
     */
    protected function getRoleModel(): Role
    {
        return app()->make(config('raptor.models.role', ModelsRole::class));
    }
}
