<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Concerns\HasAutoComplete;

/**
 * ComboboxAutoCompleteField - Campo combobox com auto-preenchimento
 *
 * Estende ComboboxField adicionando capacidade de preencher automaticamente
 * outros campos quando uma opção é selecionada.
 *
 * @example
 * ComboboxAutoCompleteField::make('product_id')
 *     ->label('Produto')
 *     ->options(Product::get(['id', 'name', 'price'])->toArray())
 *     ->autoCompleteValue('id')
 *     ->autoCompleteLabel('name')
 *     ->complete('price', 'unit_price')
 *     ->searchPlaceholder('Buscar produto...')
 *     ->required()
 */
class ComboboxAutoCompleteField extends ComboboxField
{
    use HasAutoComplete;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
    }

    public function toArray($model = null): array
    {
        $options = $this->getOptions();
        $optionsData = (object) [];

        // Processa as opções BRUTAS antes da normalização
        if (! empty($this->autoCompleteFields) || $this->optionValueKey || $this->optionLabelKey) {
            // Pega as opções brutas (antes de normalizar)
            $rawOptions = $this->evaluate($this->options);
            $processed = $this->processOptionsForAutoComplete($rawOptions);
            $options = $processed['options'];
            $optionsData = $processed['optionsData'];
        }

        // Chama o parent::toArray() do ComboboxField e sobrescreve options e optionsData
        $baseArray = parent::toArray($model);
        $baseArray['options'] = $options;
        $baseArray['optionsData'] = $optionsData;

        return array_merge($baseArray, $this->autoCompleteToArray());
    }
}
