<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Strategies;

use Illuminate\Database\Eloquent\Builder;

/**
 * FilterStrategy - Interface para estratégias de filtro
 *
 * Permite diferentes tipos de filtros serem aplicados de forma consistente
 */
interface FilterStrategy
{
    /**
     * Aplica o filtro à query
     */
    public function apply(Builder $query, string $column, mixed $value): Builder;

    /**
     * Retorna o nome da estratégia
     */
    public function getName(): string;
}
