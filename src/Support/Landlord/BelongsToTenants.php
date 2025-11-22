<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Landlord;

use Callcocam\LaravelRaptor\Support\Landlord\Exceptions\ModelNotFoundForTenantException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @mixin Model
 */
trait BelongsToTenants
{
    /**
     * @var TenantManager
     */
    protected static $landlord;

    /**
     * Boot the trait. Will apply any scopes currently set, and
     * register a listener for when new models are created.
     */
    public static function bootBelongsToTenants(): void
    {
        static::$landlord = app(TenantManager::class);

        static::$landlord->applyTenantScopes(new static);

        static::creating(fn (Model $model) => static::$landlord->newModel($model));
    }

    /**
     * Get the tenantColumns for this model.
     */
    public function getTenantColumns(): array
    {
        return $this->tenantColumns ?? config('raptor.landlord.default_tenant_columns', ['tenant_id']);
    }

    /**
     * Returns the qualified tenant column (table.tenant).
     * Override this if you need unqualified tenants (e.g., noSQL databases).
     */
    public function getQualifiedTenant(string $tenant): string
    {
        return $this->getTable().'.'.$tenant;
    }

    /**
     * Returns a new query builder without any of the tenant scopes applied.
     *
     * @example User::allTenants()->get();
     */
    public static function allTenants(): \Illuminate\Database\Eloquent\Builder
    {
        return static::$landlord->newQueryWithoutTenants(new static);
    }

    /**
     * Override the default findOrFail method so that we can re-throw
     * a more useful exception. Otherwise it can be very confusing
     * why queries don't work because of tenant scoping issues.
     *
     * @throws ModelNotFoundForTenantException
     */
    public static function findOrFail(mixed $id, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection|Model
    {
        try {
            return static::query()->findOrFail($id, $columns);
        } catch (ModelNotFoundException $e) {
            if (static::allTenants()->find($id, $columns) !== null) {
                throw (new ModelNotFoundForTenantException)->setModel(static::class);
            }

            throw $e;
        }
    }

    /**
     * Get the tenant manager instance.
     */
    public static function getTenantManager(): TenantManager
    {
        return static::$landlord;
    }
}
