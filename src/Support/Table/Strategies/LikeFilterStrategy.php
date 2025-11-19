<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Strategies;

use Illuminate\Database\Eloquent\Builder;

/**
 * LikeFilterStrategy - Filtro de busca parcial (padrão)
 *
 * Uso: WHERE column LIKE '%value%'
 */
class LikeFilterStrategy extends AbstractFilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, 'like', "%{$value}%");
    }
}
