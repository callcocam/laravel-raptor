<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToOptions
{
    /**
     * The options for the filter.
     */
    protected array|string|Closure|null $options = [];

    protected Closure|bool|null $multiple = null;

    protected Closure|string|null $optionKey = 'id';

    protected Closure|string|null $optionLabel = 'name';

    protected array $rawOptions = [];

    /**
     * Set the options for the filter.
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        $this->rawOptions = $this->evaluate($options);

        return $this;
    }

    public function rawOptions(array $options = []): static
    {
        $this->rawOptions = $options;

        return $this;
    }

    /**
     * Get the options for the filter.
     * Converte automaticamente para o formato [label, value]
     */
    public function getOptions($model = null): array
    {
        if (method_exists($this, 'hasRelationship') && $this->hasRelationship()) {
            // 3. Pegar o model relacionado de forma segura
            if ($relatedModel = $this->processRelationshipOptions()) {
                $labelColumn = $this->getOptionLabel() ?? 'name';
                $keyColumn = $this->getOptionKey() ?? 'id';

                $this->options = $relatedModel
                    ->select([$keyColumn, $labelColumn])
                    ->pluck($labelColumn, $keyColumn)
                    ->toArray();
            }
        }
        $options = $this->evaluate($this->options, ['model' => $model, 'column' => $this, 'record' => $model]);

        return $this->normalizeOptions($options);
    }

    protected function getRawOptions()
    {
        return $this->rawOptions;
    }

    /**
     * Normaliza as opções para o formato esperado [label, value]
     *
     * Aceita diversos formatos de entrada:
     * - ['key' => 'label'] => [['label' => 'label', 'value' => 'key']] (inclui id => name de pluck)
     * - [['label' => 'Teste', 'value' => '01']] => mantém o formato
     * - ['value1', 'value2'] (índices 0,1,2...) => [['label' => 'value1', 'value' => 'value1']]
     */
    protected function normalizeOptions(array $options): array
    {
        if (empty($options)) {
            return [];
        }

        $normalized = [];
        $keys = array_keys($options);
        $isSequentialList = $keys === range(0, count($options) - 1);

        foreach ($options as $key => $value) {
            // Já está no formato correto [label, value]
            if (is_array($value) && isset($value['label']) && isset($value['value'])) {
                $normalized[] = $value;

                continue;
            }

            // Formato lista sequencial: [0 => 'opt1', 1 => 'opt2'] => value e label iguais
            if ($isSequentialList && is_numeric($key) && ! is_array($value)) {
                $normalized[] = [
                    'label' => (string) $value,
                    'value' => (string) $value,
                ];

                continue;
            }

            // Formato associativo: ['key' => 'label'] ou [id => name] (ex.: pluck('name', 'id'))
            if (! is_array($value)) {
                $normalized[] = [
                    'label' => (string) $value,
                    'value' => (string) $key,
                ];

                continue;
            }

            // Formato array sem label/value definidos: [['id' => 1, 'name' => 'Test']]
            if (is_array($value)) {
                // Tenta encontrar campos comuns para label
                $labelField = $this->findLabelField($value);
                $valueField = $this->findValueField($value);

                if ($labelField && $valueField) {
                    $normalized[] = [
                        'label' => (string) $value[$labelField],
                        'value' => (string) $value[$valueField],
                    ];

                    continue;
                }
            }

            // Fallback: usa o valor como label e value
            $normalized[] = [
                'label' => is_array($value) ? json_encode($value) : (string) $value,
                'value' => is_array($value) ? json_encode($value) : (string) $value,
            ];
        }

        return $normalized;
    }

    public function optionKey(Closure|string|null $optionKey): static
    {
        $this->optionKey = $optionKey;

        return $this;
    }

    public function optionLabel(Closure|string|null $optionLabel): static
    {
        $this->optionLabel = $optionLabel;

        return $this;
    }

    public function getOptionLabel(): Closure|string|null
    {
        return $this->evaluate($this->optionLabel);
    }

    public function getOptionKey(): Closure|string|null
    {
        return $this->evaluate($this->optionKey);
    }

    /**
     * Encontra o campo mais provável para ser usado como label
     */
    protected function findLabelField(array $item): ?string
    {
        if ($optionLabel = $this->getOptionLabel()) {
            return $optionLabel;
        }
        $labelCandidates = ['label', 'name', 'title', 'text', 'description'];

        foreach ($labelCandidates as $candidate) {
            if (isset($item[$candidate])) {
                return $candidate;
            }
        }

        return array_key_first($item);
    }

    /**
     * Encontra o campo mais provável para ser usado como value
     */
    protected function findValueField(array $item): ?string
    {
        if ($optionKey = $this->getOptionKey()) {
            return $optionKey;
        }

        $valueCandidates = ['value', 'id', 'key', 'code'];

        foreach ($valueCandidates as $candidate) {
            if (isset($item[$candidate])) {
                return $candidate;
            }
        }

        return array_key_first($item);
    }

    public function multiple(bool|Closure $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->multiple);
    }
}
