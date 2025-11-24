<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class EmailField extends Column
{
    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->type('email');
        $this->component('form-field-email');
        $this->setUp();
    }
 

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'required' => $this->isRequired,
            'placeholder' => $this->placeholder ?? $this->getLabel(),
        ]);
    }
}
