<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Strategies;

use Illuminate\Database\Eloquent\Builder;

/**
 * InFilterStrategy - Filtro para valores em array
 *
 * Uso: WHERE column IN (value1, value2, ...)
 */
class InFilterStrategy extends AbstractFilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): Builder
    {
        if (! is_array($value)) {
            $value = [$value];
        }

        return $query->whereIn($column, $value);
    }
}
