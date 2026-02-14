<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Concerns;

trait HasAutoComplete
{
    protected array $autoCompleteFields = [];

    protected bool $returnFullObject = false;

    /**
     * Define um campo que será preenchido automaticamente
     *
     * @param  string|int|float|bool|null  $sourceField  Campo no objeto da opção OU valor fixo
     * @param  string  $targetField  Campo no formulário que será preenchido
     */
    public function complete(string|int|float|bool|null $sourceField, string $targetField): static
    {
        $this->autoCompleteFields[] = [
            'source' => $sourceField,
            'target' => $targetField,
            'isFixedValue' => ! is_string($sourceField) || is_numeric($sourceField),
        ];

        return $this;
    }

    /**
     * Define múltiplos campos que serão preenchidos automaticamente
     *
     * @param  array  $mappings  Array associativo ['source' => 'target', ...]
     */
    public function completeMany(array $mappings): static
    {
        foreach ($mappings as $source => $target) {
            $this->complete($source, $target);
        }

        return $this;
    }

    /**
     * Define qual campo do objeto será usado como valor da opção
     * Usa optionKey do BelongsToOptions
     *
     * @param  string  $key  Nome do campo (ex: 'id', 'uuid', 'code')
     */
    public function autoCompleteValue(string $key): static
    {
        if (method_exists($this, 'optionKey')) {
            $this->optionKey($key);
        }

        return $this;
    }

    /**
     * Define qual campo do objeto será usado como label da opção
     * Usa optionLabel do BelongsToOptions
     *
     * @param  string  $key  Nome do campo (ex: 'name', 'title', 'description')
     */
    public function autoCompleteLabel(string $key): static
    {
        if (method_exists($this, 'optionLabel')) {
            $this->optionLabel($key);
        }

        return $this;
    }

    /**
     * Define que as opções devem retornar o objeto completo
     * Permite usar optionValue e optionLabel para customizar exibição
     * mas mantém dados completos para autoComplete
     */
    public function withFullObject(bool $enabled = true): static
    {
        $this->returnFullObject = $enabled;

        return $this;
    }

    /**
     * Retorna os campos de autoComplete
     */
    public function getAutoCompleteFields(): array
    {
        return $this->autoCompleteFields;
    }

    /**
     * Retorna a chave de valor da opção
     * Usa optionKey do BelongsToOptions
     */
    public function getOptionValueKey(): ?string
    {
        return method_exists($this, 'getOptionKey') ? $this->getOptionKey() : null;
    }

    /**
     * Retorna a chave de label da opção
     * Usa optionLabel do BelongsToOptions
     */
    public function getOptionLabelKey(): ?string
    {
        return method_exists($this, 'getOptionLabel') ? $this->getOptionLabel() : null;
    }

    /**
     * Verifica se deve retornar objeto completo
     */
    public function shouldReturnFullObject(): bool
    {
        return $this->returnFullObject;
    }

    /**
     * Processa as opções para incluir dados necessários para autoComplete
     * Retorna array com 'options' (formato normal) e 'optionsData' (dados completos)
     *
     * @param  array|Collection  $options
     * @return array ['options' => [], 'optionsData' => []]
     */
    protected function processOptionsForAutoComplete($options): array
    {
        // Usa optionKey e optionLabel do BelongsToOptions
        $optionKey = method_exists($this, 'getOptionKey') ? $this->getOptionKey() : null;
        $optionLabel = method_exists($this, 'getOptionLabel') ? $this->getOptionLabel() : null;

        if (empty($this->autoCompleteFields) && ! $optionKey && ! $optionLabel) {
            // Comportamento padrão - retorna como está
            return [
                'options' => is_array($options) ? $options : $options->toArray(),
                'optionsData' => (object) [],
            ];
        }

        $normalOptions = [];
        $optionsData = [];

        foreach ($options as $key => $value) {
            // Se value é um objeto ou array, processa
            if (is_object($value) || is_array($value)) {
                $item = is_object($value) ? (array) $value : $value;

                // Define valor e label usando optionKey e optionLabel
                $optionValue = $optionKey ? ($item[$optionKey] ?? $key) : ($item['id'] ?? $key);
                $optionLabelValue = $optionLabel ? ($item[$optionLabel] ?? $value) : ($item['name'] ?? $item['label'] ?? $value);

                // Opções no formato normal (key => label)
                $normalOptions[$optionValue] = $optionLabelValue;

                // Dados completos indexados pelo valor
                $optionsData[$optionValue] = $item;
            } else {
                // Valor simples (string/number)
                $normalOptions[$key] = $value;
                $optionsData[$key] = null;
            }
        }

        return [
            'options' => $normalOptions,
            'optionsData' => empty($optionsData) ? (object) [] : $optionsData,
        ];
    }

    /**
     * Adiciona configurações de autoComplete ao array
     */
    protected function autoCompleteToArray(): array
    {
        // Usa optionKey e optionLabel do BelongsToOptions
        $optionKey = method_exists($this, 'getOptionKey') ? $this->getOptionKey() : null;
        $optionLabel = method_exists($this, 'getOptionLabel') ? $this->getOptionLabel() : null;

        return [
            'autoComplete' => [
                'enabled' => ! empty($this->autoCompleteFields),
                'fields' => $this->autoCompleteFields,
                'optionValueKey' => $optionKey,
                'optionLabelKey' => $optionLabel,
                'returnFullObject' => $this->returnFullObject,
            ],
        ];
    }
}
