<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class TextColumn extends Column
{
    public function render(mixed $value, $row = null): mixed
    {
        if ($value === null) {
            return $this->getDefault() ?? '-';
        }

        $prefix = $this->getPrefix();
        $suffix = $this->getSuffix();

        return ($prefix ? $prefix.' ' : '').$value.($suffix ? ' '.$suffix : '');
    }
}
