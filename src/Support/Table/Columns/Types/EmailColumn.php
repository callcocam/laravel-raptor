<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Table\Columns\Column;

class EmailColumn extends Column
{
    protected ?string $component = 'table-column-email';

    public function render(mixed $value, $row = null): mixed
    {
        return $this->getFormattedValue($value, $row);
    }
}
