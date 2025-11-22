<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Landlord;

use Callcocam\LaravelRaptor\Support\Landlord\Exceptions\TenantColumnUnknownException;
use Callcocam\LaravelRaptor\Support\Landlord\Exceptions\TenantNullIdException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;

class TenantManager
{
    use Macroable;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var Collection
     */
    protected $tenants;

    /**
     * @var Collection
     */
    protected $deferredModels;

    /**
     * Landlord constructor.
     */
    public function __construct()
    {
        $this->tenants = collect();
        $this->deferredModels = collect();
    }

    /**
     * Verifica se está no contexto landlord
     */
    protected function isLandlordContext(): bool
    {
        return config('app.context') === 'landlord';
    }

    /**
     * Aplica o scope apropriado baseado no contexto (landlord ou tenant)
     */
    protected function applyContextScope(Model $model, string $tenant, $id = null): void
    {
        $model->addGlobalScope($tenant, function (Builder $builder) use ($tenant, $id, $model) {
            if ($this->isLandlordContext()) {  
                $builder->whereNull($model->getQualifiedTenant($tenant));
            } else {
                if ($this->getTenants()->first() && $this->getTenants()->first() != $id) {
                    $id = $this->getTenants()->first();
                }
                $builder->where($model->getQualifiedTenant($tenant), '=', $id);
            }
        });
    }

    /**
     * Define o valor apropriado do tenant_id baseado no contexto
     */
    protected function setTenantAttribute(Model $model, string $tenantColumn, $tenantId = null): void
    {
        if (! isset($model->{$tenantColumn})) {
            $model->setAttribute($tenantColumn, $this->isLandlordContext() ? null : $tenantId);
        }
    }

    /**
     * Enable scoping by tenantColumns.
     *
     * @return void
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Disable scoping by tenantColumns.
     *
     * @return void
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Add a tenant to scope by.
     *
     * @param  string|Model  $tenant
     * @param  mixed|null  $id
     *
     * @throws TenantNullIdException
     */
    public function addTenant($tenant, $id = null)
    {
        if (func_num_args() == 1 && $tenant instanceof Model) {
            $id = $tenant->getKey();
        }

        if (is_null($id)) {
            throw new TenantNullIdException('$id must not be null');
        }

        $this->tenants->put($this->getTenantKey($tenant), $id);

        $this->applyTenantScopesToDeferredModels();
    }

    /**
     * Remove a tenant so that queries are no longer scoped by it.
     *
     * @param  string|Model  $tenant
     */
    public function removeTenant($tenant)
    {
        $this->tenants->pull($this->getTenantKey($tenant));
    }

    /**
     * Whether a tenant is currently being scoped.
     *
     * @param  string|Model  $tenant
     * @return bool
     */
    public function hasTenant($tenant)
    {
        return $this->tenants->has($this->getTenantKey($tenant));
    }

    /**
     * @return Collection
     */
    public function getTenants()
    {
        return $this->tenants;
    }

    /**
     * @return mixed
     *
     * @throws TenantColumnUnknownException
     */
    public function getTenantId($tenant)
    {
        if (! $this->hasTenant($tenant)) {
            throw new TenantColumnUnknownException(
                '$tenant must be a string key or an instance of \Illuminate\Database\Eloquent\Model'
            );
        }

        return $this->tenants->get($this->getTenantKey($tenant));
    }

    /**
     * Applies applicable tenant scopes to a model.
     *
     * @param  Model|BelongsToTenants  $model
     */
    public function applyTenantScopes(Model $model)
    {
        if (! $this->enabled) {
            return;
        }

        if (method_exists($model, 'isDefault') && $model->isDefault()) {
            return;
        }

        if ($this->isLandlordContext()) {
            collect($model->getTenantColumns())->each(function ($tenant) use ($model) {
                $this->applyContextScope($model, $tenant);
            });

            return;
        }

        if ($this->tenants->isEmpty()) {
            $this->deferredModels->push($model);

            return;
        }

        $this->modelTenants($model)->each(function ($id, $tenant) use ($model) {
            $this->applyContextScope($model, $tenant, $id);
        });
    }

    /**
     * Applies applicable tenant scopes to deferred model booted before tenants setup.
     */
    public function applyTenantScopesToDeferredModels()
    {
        $this->deferredModels->each(function ($model) {
            /* @var Model|BelongsToTenants $model */
            if ($this->isLandlordContext()) {
                collect($model->getTenantColumns())->each(function ($tenant) use ($model) {
                    $this->applyContextScope($model, $tenant);
                });
            } else {
                $this->modelTenants($model)->each(function ($id, $tenant) use ($model) {
                    $this->setTenantAttribute($model, $tenant, $id);
                    $this->applyContextScope($model, $tenant, $id);
                });
            }
        });

        $this->deferredModels = collect();
    }

    /**
     * Add tenant columns as needed to a new model instance before it is created.
     */
    public function newModel(Model $model)
    {
        if (! $this->enabled) {
            return;
        }

        // No contexto landlord, define tenant_id como NULL
        if ($this->isLandlordContext()) {
            collect($model->getTenantColumns())->each(function ($tenantColumn) use ($model) {
                $this->setTenantAttribute($model, $tenantColumn);
            });

            return;
        }

        // No contexto tenant, define o tenant_id apropriado
        if ($this->tenants->isEmpty()) {
            $this->deferredModels->push($model);

            return;
        }

        $this->modelTenants($model)->each(function ($tenantId, $tenantColumn) use ($model) {
            $this->setTenantAttribute($model, $tenantColumn, $tenantId);
        });
    }

    /**
     * Get a new Eloquent Builder instance without any of the tenant scopes applied.
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutTenants(Model $model)
    {
        return $model->newQuery()->withoutGlobalScopes($this->tenants->keys()->toArray());
    }

    /**
     * Get the key for a tenant, either from a Model instance or a string.
     *
     * @param  string|Model  $tenant
     * @return string
     *
     * @throws TenantColumnUnknownException
     */
    protected function getTenantKey($tenant)
    {
        if ($tenant instanceof Model) {
            $tenant = $tenant->getForeignKey();
        }

        if (! is_string($tenant)) {
            throw new TenantColumnUnknownException(
                '$tenant must be a string key or an instance of \Illuminate\Database\Eloquent\Model'
            );
        }

        return $tenant;
    }

    /**
     * Get the tenantColumns that are actually applicable to the given
     * model, in case they've been manually specified.
     *
     * @param  Model|BelongsToTenants  $model
     * @return Collection
     */
    protected function modelTenants(Model $model)
    {
        return $this->tenants->only($model->getTenantColumns());
    }
}
