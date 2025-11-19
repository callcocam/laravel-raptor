<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Strategies;

use Illuminate\Database\Eloquent\Builder;

/**
 * DateFilterStrategy - Filtro de data
 *
 * Uso: WHERE DATE(column) = value
 * Suporta intervalos: ['start' => '2024-01-01', 'end' => '2024-12-31']
 */
class DateFilterStrategy extends AbstractFilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): Builder
    {
        // Intervalo de datas
        if (is_array($value)) {
            if (isset($value['start']) && isset($value['end'])) {
                return $query->whereBetween($column, [$value['start'], $value['end']]);
            }

            if (isset($value['start'])) {
                return $query->where($column, '>=', $value['start']);
            }

            if (isset($value['end'])) {
                return $query->where($column, '<=', $value['end']);
            }
        }

        // Data exata
        return $query->whereDate($column, '=', $value);
    }
}
