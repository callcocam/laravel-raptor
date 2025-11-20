<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class PhoneColumn extends Column
{
    protected string $type = 'phone';

    protected ?string $component = 'info-column-phone';

    public function __construct($name, $label = null)
    {
        parent::__construct($name, $label);

        $this->icon('Phone');
    }

    public function render(mixed $value, $row = null): mixed
    {
        if ($value === null) {
            return $this->getDefault() ?? '-';
        }

        return $value;
    }
}
