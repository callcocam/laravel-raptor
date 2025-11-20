<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class TextareaField extends Column
{
    protected int $rows = 3;

    protected ?int $maxLength = null;

    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-textarea');
        $this->setUp();
    }

    public function rows(int $rows): self
    {
        $this->rows = $rows;

        return $this;
    }

    public function maxLength(int $length): self
    {
        $this->maxLength = $length;

        return $this;
    }


    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'rows' => $this->rows,
            'maxLength' => $this->maxLength,
            'required' => $this->isRequired,
            'placeholder' => $this->placeholder,
        ]);
    }
}
