<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class HiddenField extends Column
{
    protected mixed $defaultValue = null;

    public function __construct(string $name, mixed $value = null)
    {
        parent::__construct($name, '');
        $this->type('hidden');
        $this->component('form-column-hidden');
        $this->defaultValue = $value;
        $this->setUp();
    }
 

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'default' => $this->defaultValue ?? $this->default,
        ]);
    }
}
