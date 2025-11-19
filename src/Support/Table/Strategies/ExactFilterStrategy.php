<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Strategies;

use Illuminate\Database\Eloquent\Builder;

/**
 * ExactFilterStrategy - Filtro de correspondência exata
 *
 * Uso: WHERE column = value
 */
class ExactFilterStrategy extends AbstractFilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, '=', $value);
    }
}
