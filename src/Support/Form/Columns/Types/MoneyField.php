<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class MoneyField extends Column
{
    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->type('text');
        $this->component('form-field-money');
        $this->setUp();
    }
}
