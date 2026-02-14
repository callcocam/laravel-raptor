<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class PasswordField extends Column
{
    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected ?int $minLength = null;

    protected bool $showToggle = true;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->type('password');
        $this->component('form-field-password');
        $this->setUp();
    }

    public function minLength(int $length): self
    {
        $this->minLength = $length;

        if ($length && ! $this->hasRule('minLength')) {
            $this->addRule("min:$length");
        }

        return $this;
    }

    public function showToggle(bool $show = true): self
    {
        $this->showToggle = $show;

        return $this;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'required' => $this->isRequired,
            'placeholder' => $this->placeholder,
            'minLength' => $this->minLength,
            'showToggle' => $this->showToggle,
        ]);
    }
}
