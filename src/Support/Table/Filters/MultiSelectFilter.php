<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;

class MultiSelectFilter extends FilterBuilder
{
    protected string $component = 'filter-multi-select';

    protected array $options = [];

    protected bool $searchable = true;

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

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
