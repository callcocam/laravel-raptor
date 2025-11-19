<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Strategies;

use Illuminate\Database\Eloquent\Builder;

/**
 * BooleanFilterStrategy - Filtro booleano
 *
 * Uso: WHERE column = 1/0
 * Converte strings 'true'/'false', '1'/'0', 'yes'/'no'
 */
class BooleanFilterStrategy extends AbstractFilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): Builder
    {
        $boolValue = $this->toBool($value);

        return $query->where($column, '=', $boolValue);
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        $value = strtolower((string) $value);

        return in_array($value, ['true', 'yes', '1', 'on'], true);
    }
}
