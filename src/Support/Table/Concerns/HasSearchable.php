<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Concerns;

use Closure;

trait HasSearchable
{
    /**
     * The searchable state of the column.
     *
     * @var bool
     */
    protected Closure|bool $searchable = false;

    /**
     * Set the column as searchable.
     *
     * @param  bool  $searchable
     */
    public function searchable(Closure|bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Check if the column is searchable.
     */
    public function isSearchable(): bool
    {
        return $this->evaluate($this->searchable);
    }

    /**
     * Get the sortable state of the column.
     */
    public function getSearchable(): bool
    {
        return $this->evaluate($this->searchable);
    }
}
