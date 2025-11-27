<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;
use Callcocam\LaravelRaptor\Support\Form\Columns\Concerns\HasAutoComplete;
use Closure;

class SelectField extends Column
{
    use HasAutoComplete;
    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected bool $searchable = false;

    protected Closure|string|null $dependsOn = null;

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

    public function dependsOn(Closure|string|null $dependsOn): self
    {
        $this->dependsOn = $dependsOn;

        return $this;
    }

    public function getDependsOn(): Closure|string|null
    {
        return $this->evaluate($this->dependsOn);
    }

    public function toArray($model = null): array
    {
        $options = $this->getOptions();
        $optionsData = [];
        
        // Se tem autoComplete configurado, processa as opções
        if (!empty($this->autoCompleteFields) || $this->optionValueKey || $this->optionLabelKey) {
            $processed = $this->processOptionsForAutoComplete($options);
            $options = $processed['options'];
            $optionsData = $processed['optionsData'];
        }
        
        return array_merge(parent::toArray($model), [
            'searchable' => $this->searchable,
            'multiple' => $this->isMultiple(),
            'options' => $options,
            'optionsData' => $optionsData,
            'dependsOn' => $this->getDependsOn(),
        ], $this->autoCompleteToArray());
    }
}
