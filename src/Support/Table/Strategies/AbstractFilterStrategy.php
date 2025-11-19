<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Strategies;

use Illuminate\Database\Eloquent\Builder;

/**
 * AbstractFilterStrategy - Base para estratégias de filtro
 */
abstract class AbstractFilterStrategy implements FilterStrategy
{
    protected string $name;

    public function __construct(?string $name = null)
    {
        $this->name = $name ?? $this->getDefaultName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function getDefaultName(): string
    {
        $class = class_basename(static::class);

        return str_replace('FilterStrategy', '', $class);
    }

    abstract public function apply(Builder $query, string $column, mixed $value): Builder;
}
