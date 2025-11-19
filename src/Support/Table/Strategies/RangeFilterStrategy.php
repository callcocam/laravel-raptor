<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Strategies;

use Illuminate\Database\Eloquent\Builder;

/**
 * RangeFilterStrategy - Filtro de intervalo (entre valores)
 *
 * Uso: WHERE column BETWEEN min AND max
 * Espera: $value = ['min' => 10, 'max' => 100] ou [10, 100]
 */
class RangeFilterStrategy extends AbstractFilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): Builder
    {
        if (! is_array($value)) {
            return $query;
        }

        // Suporta formato ['min' => x, 'max' => y]
        if (isset($value['min']) && isset($value['max'])) {
            return $query->whereBetween($column, [$value['min'], $value['max']]);
        }

        // Suporta formato [x, y]
        if (count($value) === 2 && isset($value[0]) && isset($value[1])) {
            return $query->whereBetween($column, $value);
        }

        // Apenas min
        if (isset($value['min'])) {
            return $query->where($column, '>=', $value['min']);
        }

        // Apenas max
        if (isset($value['max'])) {
            return $query->where($column, '<=', $value['max']);
        }

        return $query;
    }
}
