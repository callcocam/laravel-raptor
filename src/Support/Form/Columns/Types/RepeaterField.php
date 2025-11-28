<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToFields;
use Callcocam\LaravelRaptor\Support\Form\Columns\Column;
use Illuminate\Support\Facades\Log;
class RepeaterField extends Column
{
    use BelongsToFields;

    protected ?int $minItems = null;

    protected ?int $maxItems = null;

    protected ?string $addButtonLabel = 'Adicionar item';

    protected ?string $removeButtonLabel = 'Remover';

    protected array $defaultItems = [];

    protected bool $collapsible = false;

    protected bool $orderable = false;

    protected bool $compact = false;

    protected array $calculations = [];

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-repeater');
        $this->setUp();

        $this->valueUsing(function ($data, $model) {
            $currentValue = data_get($data, $this->getName(), null);
            if (is_null($currentValue)) {
                return [];
            }
            $fields = $this->getFields();
            $newValue = [];
            foreach ($currentValue as $key => $values) {
                $data = [];
                foreach ($values as $columnName => $value) {
                    $field = collect($fields)->firstWhere('name', $columnName);
                    if ($field) { 
                        $field->index($key);
                        $valueUsing = $field->getValueUsing($values, $model);
                        if ($valueUsing !== null) {
                            if (is_array($valueUsing)) {
                                $data = array_merge($data, $valueUsing);
                            } else {
                                $data[$columnName] = $valueUsing;
                            }
                        } else {
                            $data[$columnName] = $value;
                        }
                    } else {
                        $data[$columnName] = $value;
                    }
                }
                $newValue[$key] = array_merge($values, $data);
            }

            return [
                $this->getName() => $newValue,
            ];
        });

        $this->defaultUsing(function ($data, $model) {
            $currentValue = data_get($model, $this->getName(), null);
            if (is_null($currentValue)) {
                return [];
            }

            $fields = $this->getFields();
            $newValue = [];

            // Se for uma Collection do Eloquent, converte para array
            if (method_exists($currentValue, 'toArray')) {
                $currentValue = $currentValue->all(); // Pega os modelos da collection
            }
            
            foreach ($currentValue as $key => $item) {
                $processedData = [];

                // Se for um modelo Eloquent, extrai os atributos
                $itemData = is_object($item) && method_exists($item, 'getAttributes')
                    ? $item->getAttributes()
                    : (is_array($item) ? $item : (array) $item);

                foreach ($fields as $field) {
                    $columnName = $field->getName();
                    $rawValue = $itemData[$columnName] ?? null;
                    Log::info('key', [$key]);
                    // Aplica defaultUsing de cada campo interno
                    // Passa os dados do item para o campo processar
                    $field->index($key);
                    $defaultUsing = $field->getDefaultUsing([$columnName => $rawValue], $item);
                    if ($defaultUsing !== null) {
                        if (is_array($defaultUsing)) {
                            $processedData = array_merge($processedData, $defaultUsing);
                        } else {
                            $processedData[$columnName] = $defaultUsing;
                        }
                    } else {
                        $processedData[$columnName] = $rawValue;
                    }
                }

                $newValue[$key] = $processedData;
            }
            return $newValue;
        });
    }

    public function minItems(int $min): self
    {
        $this->minItems = $min;

        return $this;
    }

    public function maxItems(int $max): self
    {
        $this->maxItems = $max;

        return $this;
    }

    public function addButtonLabel(string $label): self
    {
        $this->addButtonLabel = $label;

        return $this;
    }

    public function removeButtonLabel(string $label): self
    {
        $this->removeButtonLabel = $label;

        return $this;
    }

    public function defaultItems(array $items): self
    {
        $this->defaultItems = $items;

        return $this;
    }

    public function collapsible(bool $collapsible = true): self
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    public function orderable(bool $orderable = true): self
    {
        $this->orderable = $orderable;

        return $this;
    }

    public function compact(bool $compact = true): self
    {
        $this->compact = $compact;

        return $this;
    }

    /**
     * Adiciona um cálculo de soma nos itens do repeater
     *
     * @param  string  $sourceField  Campo que será somado
     * @param  array  $targetFields  Campos onde o resultado será exibido
     */
    public function sum(string $sourceField, array $targetFields): self
    {
        $this->calculations[] = [
            'type' => 'sum',
            'sourceField' => $sourceField,
            'targetFields' => $targetFields,
        ];

        return $this;
    }

    /**
     * Adiciona um cálculo de média nos itens do repeater
     *
     * @param  string  $sourceField  Campo que será calculado a média
     * @param  array  $targetFields  Campos onde o resultado será exibido
     */
    public function avg(string $sourceField, array $targetFields): self
    {
        $this->calculations[] = [
            'type' => 'avg',
            'sourceField' => $sourceField,
            'targetFields' => $targetFields,
        ];

        return $this;
    }

    /**
     * Adiciona um cálculo de mínimo nos itens do repeater
     *
     * @param  string  $sourceField  Campo que será analisado
     * @param  array  $targetFields  Campos onde o resultado será exibido
     */
    public function min(string $sourceField, array $targetFields): self
    {
        $this->calculations[] = [
            'type' => 'min',
            'sourceField' => $sourceField,
            'targetFields' => $targetFields,
        ];

        return $this;
    }

    /**
     * Adiciona um cálculo de máximo nos itens do repeater
     *
     * @param  string  $sourceField  Campo que será analisado
     * @param  array  $targetFields  Campos onde o resultado será exibido
     */
    public function max(string $sourceField, array $targetFields): self
    {
        $this->calculations[] = [
            'type' => 'max',
            'sourceField' => $sourceField,
            'targetFields' => $targetFields,
        ];

        return $this;
    }

    /**
     * Adiciona um cálculo de contagem nos itens do repeater
     *
     * @param  string  $sourceField  Campo que será contado (valores não vazios)
     * @param  array  $targetFields  Campos onde o resultado será exibido
     */
    public function count(string $sourceField, array $targetFields): self
    {
        $this->calculations[] = [
            'type' => 'count',
            'sourceField' => $sourceField,
            'targetFields' => $targetFields,
        ];

        return $this;
    }

    /**
     * Adiciona um cálculo customizado nos itens do repeater
     *
     * @param  string  $type  Tipo de cálculo customizado
     * @param  string  $sourceField  Campo fonte
     * @param  array  $targetFields  Campos onde o resultado será exibido
     * @param  array  $options  Opções adicionais para o cálculo
     */
    public function calculate(string $type, string $sourceField, array $targetFields, array $options = []): self
    {
        $this->calculations[] = array_merge([
            'type' => $type,
            'sourceField' => $sourceField,
            'targetFields' => $targetFields,
        ], $options);

        return $this;
    }

    public function toArray($model = null): array
    {
        // Converte cada field para array
        $fieldsArray = array_map(function ($field) use ($model) {
            return $field->toArray($model);
        }, $this->getFields());

        return array_merge(parent::toArray($model), [
            'minItems' => $this->minItems,
            'maxItems' => $this->maxItems,
            'addButtonLabel' => $this->addButtonLabel ?? 'Adicionar item',
            'removeButtonLabel' => $this->removeButtonLabel ?? 'Remover',
            'defaultItems' => $this->defaultItems,
            'collapsible' => $this->collapsible,
            'orderable' => $this->orderable,
            'compact' => $this->compact,
            'fields' => $fieldsArray,
            'calculations' => $this->calculations,
        ]);
    }
}
