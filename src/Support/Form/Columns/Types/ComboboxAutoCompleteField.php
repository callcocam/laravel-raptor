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
        $optionsData = (object) [];

        // Processa as opções BRUTAS antes da normalização
        $optionKey = $this->getOptionKey();
        $optionLabel = $this->getOptionLabel();
        if (! empty($this->autoCompleteFields) || $optionKey || $optionLabel) {
            // Pega as opções brutas (antes de normalizar)
            $processed = $this->processOptionsForAutoComplete($this->getRawOptions());
            $optionsData = $processed['optionsData'];
        }

        $baseArray = parent::toArray($model);
        $baseArray['optionsData'] = $optionsData;

        return array_merge($baseArray, $this->autoCompleteToArray());
    }
}
