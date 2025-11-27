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
 * ComboboxField - Campo de seleção com busca (autocomplete)
 *
 * Similar ao SelectField mas com busca embutida para melhor UX
 * quando há muitas opções.
 *
 * @example
 * ComboboxField::make('category_id')
 *     ->label('Categoria')
 *     ->options(Category::pluck('name', 'id'))
 *     ->searchPlaceholder('Buscar categoria...')
 *     ->emptyText('Nenhuma categoria encontrada')
 *     ->required()
 */
class ComboboxField extends Column
{
    use HasAutoComplete;
    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected ?string $searchPlaceholder = null;

    protected ?string $emptyText = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-combobox');
        $this->setUp();
    }

    /**
     * Define o placeholder do campo de busca
     *
     * @param string $placeholder Texto do placeholder (ex: "Buscar...")
     * @return self
     */
    public function searchPlaceholder(string $placeholder): self
    {
        $this->searchPlaceholder = $placeholder;

        return $this;
    }

    /**
     * Define o texto exibido quando não há resultados
     *
     * @param string $text Texto a exibir (ex: "Nenhum resultado encontrado")
     * @return self
     */
    public function emptyText(string $text): self
    {
        $this->emptyText = $text;

        return $this;
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
            'options' => $options,
            'optionsData' => $optionsData,
            'searchPlaceholder' => $this->searchPlaceholder,
            'emptyText' => $this->emptyText,
        ], $this->autoCompleteToArray());
    }
}
