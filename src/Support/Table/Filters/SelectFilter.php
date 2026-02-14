<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToOptions;
use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;

class SelectFilter extends FilterBuilder
{
    use BelongsToOptions;

    protected string $component = 'filter-select';

    protected function setUp(): void
    {
        $this->queryUsing(function ($query, $value) {
            $query->where($this->getName(), $value);
        });
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->getOptions(),
        ]);
    }
}
