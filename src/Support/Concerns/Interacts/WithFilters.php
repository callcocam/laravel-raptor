<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */


namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\Concerns\ManagesCollection;
use Callcocam\LaravelRaptor\Support\Table\Filter;

trait WithFilters
{
    use ManagesCollection;

    public function filters(array $filters): static
    {
        return $this->addManyToCollection($filters, 'filters');
    }

    public function filter(Filter $filter): static
    {
        return $this->addToCollection($filter, 'filters');
    }

    /**
     * @return array<Filter>
     */
    public function getArrayFilters(): array
    {
        return array_values(array_filter(
            array_map(function ($filter) {
                if (data_get($filter, 'visible', true) === false) {
                    return null;
                }
                return $filter;
            }, $this->getCollectionAsArray('filters'))
        ));
    }

    public function getFilters(): array
    {
        return $this->getCollection('filters');
    }
}
