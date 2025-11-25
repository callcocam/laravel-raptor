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

    protected Closure|string|null $optionKey = "id";

    protected Closure|string|null $optionLabel = 'name';

    /**
     * Set the options for the filter.
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get the options for the filter.
     * Converte automaticamente para o formato [label, value]
     */
    public function getOptions(): array
    {
        if (method_exists($this, 'hasRelationship') && $this->hasRelationship()) {
            $relationship = $this->getRelationship();

            if ($relationship) {
                // 1. Validar que o relacionamento existe no model
                $record = $this->getRecord();

                if (!method_exists($record, $relationship)) {
                    throw new \InvalidArgumentException("Relationship '{$relationship}' does not exist.");
                }

                // 2. Verificar se é realmente um relacionamento válido
                try {
                    $relationInstance = $record->$relationship();

                    if (!$relationInstance instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        throw new \InvalidArgumentException("'{$relationship}' is not a valid relationship.");
                    }

                    // 3. Pegar o model relacionado de forma segura
                    $relatedModel = $this->getUsingRelationshipQuery($relationInstance->getRelated()); 
                    // 4. Validar nomes das colunas para evitar injeção
                    $labelColumn = $this->getOptionLabel() ?? 'name';
                    $keyColumn = $this->getOptionKey() ?? 'id';

                    $this->options = $relatedModel
                        ->select([$keyColumn, $labelColumn])
                        ->pluck($labelColumn, $keyColumn)
                        ->toArray();
                } catch (\Throwable $e) {
                    \Log::error('Error loading relationship options: ' . $e->getMessage());
                    dd($e->getMessage());
                    $this->options = [];
                }
            }
        }
        $options = $this->evaluate($this->options);

        return $this->normalizeOptions($options);
    }

    /**
     * Normaliza as opções para o formato esperado [label => value]
     * 
     * Aceita diversos formatos de entrada:
     * - ['key' => 'value'] => [['label' => 'value', 'value' => 'key']]
     * - [['label' => 'Teste', 'value' => '01']] => mantém o formato
     * - ['value1', 'value2'] => [['label' => 'value1', 'value' => 'value1']]
     */
    protected function normalizeOptions(array $options): array
    {
        if (empty($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $key => $value) {
            // Já está no formato correto [label, value]
            if (is_array($value) && isset($value['label']) && isset($value['value'])) {
                $normalized[] = $value;
                continue;
            }

            // Formato associativo: ['key' => 'label']
            if (!is_numeric($key) && !is_array($value)) {
                $normalized[] = [
                    'label' => (string) $value,
                    'value' => (string) $key,
                ];
                continue;
            }

            // Formato numérico simples: ['option1', 'option2']
            if (is_numeric($key) && !is_array($value)) {
                $normalized[] = [
                    'label' => (string) $value,
                    'value' => (string) $value,
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
