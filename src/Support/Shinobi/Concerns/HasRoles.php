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
        return $this->belongsToMany(config('raptor.shinobi.models.role', ModelsRole::class))->withTimestamps();
    }

    /**
     * Checks if the model has the given role assigned.
     *
     * @param  string  $role
     */
    public function hasRole($role): bool
    {
        // Garante que roles estão carregadas (evita N+1)
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }

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
     * @return array
     */
    protected function getRoles(array $roles)
    {
        $roleModel = $this->getRoleModel();
        $slugs = [];
        $ids = [];

        // Separa slugs de IDs/models
        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $ids[] = $role->id;
            } else {
                $slugs[] = $role;
            }
        }

        // Busca todas as roles de uma vez (1 query em vez de N)
        if (!empty($slugs)) {
            $found = $roleModel->whereIn('slug', $slugs)->pluck('id')->toArray();
            $ids = array_merge($ids, $found);
        }

        return $ids;
    }

    public function hasPermissionRoleFlags()
    {
        if ($this->hasRoles()) {
            // Garante que as roles estão carregadas
            if (!$this->relationLoaded('roles')) {
                $this->load('roles');
            }

            return $this->roles
                ->filter(function ($role) {
                    return $role->special;
                })->count() >= 1;
        }

        return false;
    }

    /**
     * Verifica se o usuário é administrador
     */
    public function isAdmin(): bool
    {
        if ($this->hasRoles()) {
            // Garante que as roles estão carregadas
            if (!$this->relationLoaded('roles')) {
                $this->load('roles');
            }
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
        return app()->make(config('raptor.shinobi.models.role', ModelsRole::class));
    }
}
