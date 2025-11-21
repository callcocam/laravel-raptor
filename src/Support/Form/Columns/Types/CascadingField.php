<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToFields;
use Callcocam\LaravelRaptor\Support\Form\Columns\Column;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class CascadingField extends Column
{
    use BelongsToFields;

    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected bool $searchable = false;

    protected Closure|string|Builder|null $queryUsing = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-cascading');
        $this->setUp();
    }

    public function queryUsing(Closure|string|Builder|null $queryUsing): self
    {
        $this->queryUsing = $queryUsing;

        return $this;
    }

    public function getQueryUsing(): Closure|string|Builder|null
    {
        if (is_string($this->queryUsing)) {
            return app($this->queryUsing);
        }
        if ($this->queryUsing instanceof Builder) {
            return $this->queryUsing;
        }
        return $this->evaluate($this->queryUsing);
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    protected function cascadingFields(array $fields): array
    {
        $queryUsing = $this->getQueryUsing();
        $cascadingFields = [];

        foreach ($fields as  $field) {
            $query = clone $queryUsing;
            $dependency = $field->getDependsOn();

            if ($dependency) {
                // Se tem dependência, filtra baseado no valor do campo pai
                $dependencyValue = request()->input($dependency);
                if ($dependencyValue) {
                    $query->where($this->getFieldsUsing(), $dependencyValue);
                    $field->options($query->pluck($this->getOptionLabel(),  $this->getOptionKey())->toArray());
                } else {
                    // Se não tem valor do campo pai, deixa vazio
                    $field->options([]);
                }
            } else {
                // Se não tem dependência, busca os registros raiz (whereNull)
                $query->whereNull($this->getFieldsUsing());
                $field->options($query->pluck($this->getOptionLabel(),  $this->getOptionKey())->toArray());
            }

            $cascadingFields[] = $field;
        }

        return $cascadingFields;
    }

    public function toArray(): array
    {
        $cascadingFields = $this->cascadingFields($this->getFields());

        // Converte cada field para array
        $fieldsArray = array_map(function ($field) {
            return $field->toArray();
        }, $cascadingFields);

        return  array_merge([
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'default' => $this->getDefault(),
            'helpText' => $this->getHelpText(),
            'component' => $this->getComponent(),
            'fields' => $fieldsArray,
        ], $this->getGridLayoutConfig());
    }
}
