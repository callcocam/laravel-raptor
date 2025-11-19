<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class CheckboxField extends Column
{
    protected bool $isRequired = false;

    protected ?string $description = null;

    protected bool $defaultValue = false;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-column-checkbox');
        $this->setUp();
    }

    public function required(bool $required = true): self
    {
        $this->isRequired = $required;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }
 

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'required' => $this->isRequired,
            'description' => $this->description,
            'default' => $this->defaultValue,
        ]);
    }
}
