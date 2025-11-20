<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class EmailColumn extends Column
{
    protected ?string $component = 'info-column-email';

    public function __construct($name, $label = null)
    {
        parent::__construct($name, $label);

        $this->icon('Mail');
    }

    public function render(mixed $value, $row = null): mixed
    {
        if ($value === null) {
            return $this->getDefault() ?? '-';
        }

        return $value;
    }
}
