<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasCustomScopes
 *
 * Permite aplicar scopes personalizados automaticamente em queries
 *
 * @example
 * class Order extends AbstractModel
 * {
 *     use HasCustomScopes;
 *
 *     // Scope aplicado automaticamente
 *     protected function applyScopes(Builder $query): Builder
 *     {
 *         return $query->where('status', 'active');
 *     }
 *
 *     // Scope baseado em contexto de domínio
 *     protected function applyDomainContext(Builder $query): Builder
 *     {
 *         if ($clientId = config('app.current_client_id')) {
 *             return $query->where('client_id', $clientId);
 *         }
 *         return $query;
 *     }
 * }
 *
 * // Uso:
 * Order::query() // Automaticamente aplica scopes
 * Order::withoutScopes()->get() // Ignora scopes
 */
trait HasCustomScopes
{
    /**
     * Boot do trait - registra global scope
     */
    protected static function bootHasCustomScopes()
    {
        static::addGlobalScope('custom_scopes', function (Builder $builder) {
            $model = $builder->getModel();

            // Aplica todos os métodos apply*
            $model->applyCustomScopes($builder);
        });
    }

    /**
     * Aplica todos os scopes personalizados do modelo
     */
    protected function applyCustomScopes(Builder $query): Builder
    {
        $methods = get_class_methods($this);

        foreach ($methods as $method) {
            // Busca métodos que começam com 'apply' (exceto applyCustomScopes)
            if (str_starts_with($method, 'apply') && $method !== 'applyCustomScopes') {
                $query = $this->$method($query);
            }
        }

        return $query;
    }

    /**
     * Scope para remover todos os custom scopes
     */
    public function scopeWithoutCustomScopes(Builder $query): Builder
    {
        return $query->withoutGlobalScope('custom_scopes');
    }

    /**
     * Alias para withoutCustomScopes
     */
    public function scopeWithoutScopes(Builder $query): Builder
    {
        return $query->withoutCustomScopes();
    }

    /**
     * Scope para aplicar apenas um scope específico
     */
    public function scopeOnlyScope(Builder $query, string $scopeName): Builder
    {
        $query->withoutCustomScopes();

        $method = 'apply'.ucfirst($scopeName);

        if (method_exists($this, $method)) {
            return $this->$method($query);
        }

        return $query;
    }
}
