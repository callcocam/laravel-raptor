<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class SelectField extends Column
{
    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected bool $searchable = false;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-select');
        $this->setUp();
    }
 

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [ 
            'searchable' => $this->searchable,
            'multiple' => $this->isMultiple(),
            'options' => $this->getOptions(),   
        ]);
    }
}
