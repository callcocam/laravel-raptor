<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToFields;
use Callcocam\LaravelRaptor\Support\Form\Columns\Column;


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

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-repeater');
        $this->setUp();
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
            'fields' => $fieldsArray,
        ]);
    }
}
