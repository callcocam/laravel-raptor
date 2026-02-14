<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Shinobi\Concerns;

use Callcocam\LaravelRaptor\Models\Permission as ModelsPermission;
use Callcocam\LaravelRaptor\Support\Shinobi\Contracts\Permission;
use Callcocam\LaravelRaptor\Support\Shinobi\Exceptions\PermissionNotFoundException;
use Callcocam\LaravelRaptor\Support\Shinobi\Facades\Shinobi;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

trait HasPermissions
{
    /**
     * Users can have many permissions
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(config('raptor.shinobi.models.permission', ModelsPermission::class))->withTimestamps();
    }

    /**
     * The mothergoose check. Runs through each scenario provided
     * by Shinobi - checking for special flags, role permissions, and
     * individual user permissions; in that order.
     *
     * @param  Permission|string  $permission
     */
    public function hasPermissionTo($permission): bool
    {
        // Check role flags
        if ((method_exists($this, 'hasPermissionRoleFlags') and $this->hasPermissionRoleFlags())) {
            return $this->hasPermissionThroughRoleFlag();
        }
        if ((method_exists($this, 'hasPermissionFlags') and $this->hasPermissionFlags())) {
            return $this->hasPermissionThroughFlag();
        }
        $model = null;
        // Fetch permission if we pass through a string
        if (is_string($permission)) {
            $model = $this->getPermissionModel()->where('slug', $permission)->first();

            if (! $model) {
                throw new PermissionNotFoundException("There is no permission named [$permission] in the system. Please check your permissions setup in Shinobi.");
            }
        }

        // Check role permissions
        if (method_exists($this, 'hasPermissionThroughRole') and $this->hasPermissionThroughRole($model ?? $permission)) {
            return true;
        }

        // Check user permission
        if ($this->hasPermission($permission)) {
            return true;
        }

        return false;
    }

    /**
     * Give the specified permissions to the model.
     *
     * @param  array  $permissions
     */
    public function givePermissionTo(...$permissions): self
    {
        $permissions = Arr::flatten($permissions);
        $permissions = $this->getPermissions($permissions);

        if (! $permissions) {
            return $this;
        }

        $this->permissions()->syncWithoutDetaching($permissions);

        return $this;
    }

    /**
     * Revoke the specified permissions from the model.
     *
     * @param  array  $permissions
     */
    public function revokePermissionTo(...$permissions): self
    {
        $permissions = Arr::flatten($permissions);
        $permissions = $this->getPermissions($permissions);

        $this->permissions()->detach($permissions);

        return $this;
    }

    /**
     * Sync the specified permissions against the model.
     *
     * @param  array  $permissions
     */
    public function syncPermissions(...$permissions): self
    {
        $permissions = Arr::flatten($permissions);
        $permissions = $this->getPermissions($permissions);

        $this->permissions()->sync($permissions);

        return $this;
    }

    /**
     * Get the specified permissions.
     *
     * @param  array  $permissions
     * @return array
     */
    protected function getPermissions(array $collection)
    {
        $permissionModel = $this->getPermissionModel();
        $slugs = [];
        $ids = [];

        // Separa slugs de IDs/models
        foreach ($collection as $permission) {
            if ($permission instanceof Permission) {
                $ids[] = $permission->id;
            } else {
                $slugs[] = $permission;
            }
        }

        // Busca todas as permissions de uma vez (1 query em vez de N)
        if (! empty($slugs)) {
            if ($permissionModel instanceof \Illuminate\Database\Eloquent\Collection) {
                // Cache habilitado: busca in-memory
                $found = $permissionModel->whereIn('slug', $slugs)->pluck('id')->toArray();
            } else {
                // Sem cache: query única
                $found = $permissionModel->whereIn('slug', $slugs)->pluck('id')->toArray();
            }
            $ids = array_merge($ids, $found);
        }

        return $ids;
    }

    /**
     * Checks if the user has the given permission assigned.
     *
     * @param  \Callcocam\LaravelRaptor\Support\Shinobi\Models\Permission  $permission
     */
    protected function hasPermission($permission): bool
    {
        // Garante que permissions estão carregadas (evita N+1)
        if (! $this->relationLoaded('permissions')) {
            $this->load('permissions');
        }

        if ($permission instanceof Permission) {
            $permission = $permission->slug;
        }

        return (bool) $this->permissions->where('slug', $permission)->count();
    }

    /**
     * Get the model instance responsible for permissions.
     *
     * @return \Callcocam\LaravelRaptor\Support\Shinobi\Contracts\Permission|\Illuminate\Database\Eloquent\Collection
     */
    protected function getPermissionModel()
    {
        if (config('raptor.shinobi.cache.enabled')) {
            return cache()->tags(config('raptor.shinobi.cache.tag'))->remember(
                'permissions',
                config('raptor.shinobi.cache.length'),
                function () {
                    return app()->make(config('raptor.shinobi.models.permission'))->get();
                }
            );
        }

        return app()->make(config('raptor.shinobi.models.permission'));
    }
}
