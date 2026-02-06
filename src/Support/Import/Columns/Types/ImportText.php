<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Columns\Types;

use Callcocam\LaravelRaptor\Support\Import\Columns\Column;

class ImportText extends Column
{
    public function render(mixed $value, $row = null): mixed
    {
        return (string) $value;
    }
}
