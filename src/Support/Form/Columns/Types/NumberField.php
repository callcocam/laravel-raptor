<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class NumberField extends Column
{
    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected ?float $min = null;

    protected ?float $max = null;

    protected ?float $step = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->type('number');
        $this->component('form-field-number');
        $this->setUp();
    }
 

    public function min(float $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function max(float $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function step(float $step): self
    {
        $this->step = $step;

        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'required' => $this->isRequired,
            'placeholder' => $this->placeholder,
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
        ]);
    }
}
