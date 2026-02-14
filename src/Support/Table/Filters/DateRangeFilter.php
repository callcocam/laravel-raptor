<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;

class DateRangeFilter extends FilterBuilder
{
    protected string $component = 'filter-date-range';

    protected function setUp(): void
    {
        $this->queryUsing(function ($query, $value) {
            if (is_array($value)) {
                $from = $value['from'] ?? null;
                $to = $value['to'] ?? null;

                if ($from) {
                    $query->whereDate($this->getName(), '>=', $from);
                }

                if ($to) {
                    $query->whereDate($this->getName(), '<=', $to);
                }
            }
        });
    }
}
