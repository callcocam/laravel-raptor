<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */


namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts; 

use Callcocam\LaravelRaptor\Support\Table\Filter;

trait InteractWithFilters
{
    protected array $filters = [];

    public function filters(array $filters): static
    {
        foreach ($filters as $filter) {
            $this->filter($filter);
        }

        return $this;
    }

    public function filter(Filter $filter): static
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @return array<Filter>
     */
    public function getArrayFilters(): array
    {
        return array_map(fn (Filter $filter) => $filter->toArray(), $this->filters);
    }

    public function getFilters(): array
    {
        return $this->filters;
    }
}
