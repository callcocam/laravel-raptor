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
    protected ?string $optionValueKey = null;
    protected ?string $optionLabelKey = null;
    protected bool $returnFullObject = false;

    /**
     * Define um campo que será preenchido automaticamente
     * 
     * @param string $sourceField Campo no objeto da opção
     * @param string $targetField Campo no formulário que será preenchido
     * @return static
     */
    public function complete(string $sourceField, string $targetField): static
    {
        $this->autoCompleteFields[] = [
            'source' => $sourceField,
            'target' => $targetField,
        ];
        
        return $this;
    }

    /**
     * Define múltiplos campos que serão preenchidos automaticamente
     * 
     * @param array $mappings Array associativo ['source' => 'target', ...]
     * @return static
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
     * 
     * @param string $key Nome do campo (ex: 'id', 'uuid', 'code')
     * @return static
     */
    public function autoCompleteValue(string $key): static
    {
        $this->optionValueKey = $key;
        
        return $this;
    }

    /**
     * Define qual campo do objeto será usado como label da opção
     * 
     * @param string $key Nome do campo (ex: 'name', 'title', 'description')
     * @return static
     */
    public function autoCompleteLabel(string $key): static
    {
        $this->optionLabelKey = $key;
        
        return $this;
    }

    /**
     * Define que as opções devem retornar o objeto completo
     * Permite usar optionValue e optionLabel para customizar exibição
     * mas mantém dados completos para autoComplete
     * 
     * @param bool $enabled
     * @return static
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
     */
    public function getOptionValueKey(): ?string
    {
        return $this->optionValueKey;
    }

    /**
     * Retorna a chave de label da opção
     */
    public function getOptionLabelKey(): ?string
    {
        return $this->optionLabelKey;
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
     * @param array|Collection $options
     * @return array ['options' => [], 'optionsData' => []]
     */
    protected function processOptionsForAutoComplete($options): array
    {
        if (empty($this->autoCompleteFields) && !$this->optionValueKey && !$this->optionLabelKey) {
            // Comportamento padrão - retorna como está
            return [
                'options' => is_array($options) ? $options : $options->toArray(),
                'optionsData' => [],
            ];
        }

        $normalOptions = [];
        $optionsData = [];

        foreach ($options as $key => $value) {
            // Se value é um objeto ou array, processa
            if (is_object($value) || is_array($value)) {
                $item = is_object($value) ? (array) $value : $value;
                
                // Define valor e label
                $optionValue = $this->optionValueKey ? ($item[$this->optionValueKey] ?? $key) : ($item['id'] ?? $key);
                $optionLabel = $this->optionLabelKey ? ($item[$this->optionLabelKey] ?? $value) : ($item['name'] ?? $item['label'] ?? $value);
                
                // Opções no formato normal (key => label)
                $normalOptions[$optionValue] = $optionLabel;
                
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
            'optionsData' => $optionsData,
        ];
    }

    /**
     * Adiciona configurações de autoComplete ao array
     */
    protected function autoCompleteToArray(): array
    {
        return [
            'autoComplete' => [
                'enabled' => !empty($this->autoCompleteFields),
                'fields' => $this->autoCompleteFields,
                'optionValueKey' => $this->optionValueKey,
                'optionLabelKey' => $this->optionLabelKey,
                'returnFullObject' => $this->returnFullObject,
            ],
        ];
    }
}
