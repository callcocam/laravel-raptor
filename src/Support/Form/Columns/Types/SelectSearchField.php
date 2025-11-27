<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;
use Callcocam\LaravelRaptor\Support\Form\Columns\Concerns\HasAutoComplete;

/**
 * SelectAutoCompleteField - Campo de seleção com auto-preenchimento
 *
 * Estende SelectField adicionando capacidade de preencher automaticamente
 * outros campos quando uma opção é selecionada.
 *
 * @example
 * SelectAutoCompleteField::make('product_id')
 *     ->label('Produto')
 *     ->options(Product::get(['id', 'name', 'price'])->toArray())
 *     ->autoCompleteValue('id')
 *     ->autoCompleteLabel('name')
 *     ->complete('price', 'unit_price')
 *     ->required()
 */
class SelectSearchField extends Column
{
    use HasAutoComplete;

    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected bool $searchable = false;

    protected Closure|string|null $dependsOn = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-search-select');
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

        $optionsData = (object) [];

        // Processa as opções BRUTAS antes da normalização
        if (! empty($this->autoCompleteFields) || $this->optionValueKey || $this->optionLabelKey) {
            // Pega as opções brutas (antes de normalizar) 
            $processed = $this->processOptionsForAutoComplete($this->getRawOptions());
            $optionsData = $processed['optionsData'];
        }

        $baseArray = array_merge(parent::toArray($model), [
            'searchable' => $this->searchable,
            'multiple' => $this->isMultiple(),
            'options' => $this->getOptions(),
            'dependsOn' => $this->getDependsOn(),
        ]);
        $baseArray['optionsData'] = $optionsData;

        return array_merge($baseArray, $this->autoCompleteToArray());
    }
}
