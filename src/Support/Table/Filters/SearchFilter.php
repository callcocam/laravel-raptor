<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;

class SearchFilter extends FilterBuilder
{
    protected string $component = 'filter-text';

    protected function setUp(): void
    {
        $this->queryUsing(function ($query, $value) {
            $query->where($this->getName(), 'like', "%{$value}%");
        });
    }
}
