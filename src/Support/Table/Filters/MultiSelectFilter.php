<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToOptions;
use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;

class MultiSelectFilter extends FilterBuilder
{
    use BelongsToOptions;

    protected string $component = 'filter-multi-select';

    protected bool $searchable = true;

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    protected function setUp(): void
    {
        $this->queryUsing(function ($query, $value) {
            if (is_array($value)) {
                $query->whereIn($this->getName(), $value);
            } else {
                $query->where($this->getName(), $value);
            }
        });
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->getOptions(),
            'searchable' => $this->isSearchable(),
        ]);
    }
}
