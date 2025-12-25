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

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-cascading');
        $this->setUp();
        $this->valueUsing(function ($data, $model) {
            $currentValue = data_get($data, $this->getName(), []);

            // Se não for array, converte para array vazio
            if (!is_array($currentValue)) {
                $currentValue = [];
            }

            // Pega o último valor não vazio da hierarquia
            $lastValue = null;
            foreach ($currentValue as $key => $value) {
                if (!empty($value)) {
                    $lastValue = $value;
                }
            }

            // Adiciona o último valor selecionado no campo fieldsUsing
            if ($lastValue) {
                return [
                    $this->getFieldsUsing() => $lastValue,
                    $this->getName() => $currentValue,
                ];
            }
            return [
                $this->getName() => $currentValue,
            ];
        });
    }


    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    protected function cascadingFields($model = null): array
    {
        $fields = $this->getFields();
        $queryUsing = $this->getQueryUsing();
        $cascadingFields = [];

        // Pega os dados do cascading do modelo (se existir)
        $cascadingData = null;
        if ($model) {
            $cascadingAttribute = $this->getName();
            $cascadingData = $model->{$cascadingAttribute} ?? [];
        }

        foreach ($fields as  $field) {
            $query =  null;
            if (method_exists($field, 'getQueryUsing')) {
                $fieldQueryUsing = $field->getQueryUsing();
                if ($fieldQueryUsing instanceof Builder) {
                    $query = clone $fieldQueryUsing;
                } else {
                    $query = clone $queryUsing;
                }
            } else {
                $query = clone $queryUsing;
            }
            $dependency = $field->getDependsOn();

            if ($dependency) {
                // Prioridade 1: pega da URL query (quando o usuário seleciona um campo)
                $dependencyValue = request()->query($dependency);

                // Prioridade 2: pega do modelo (quando está carregando a página de edição)
                if (!$dependencyValue && $cascadingData && isset($cascadingData[$dependency])) {
                    $dependencyValue = $cascadingData[$dependency];
                }

                if ($dependencyValue) {
                    if ($fieldUsing = $this->getFieldsUsing()) {
                        // Se for campo de relacionamento, filtra pelo campo correto
                        $query->where($fieldUsing, $dependencyValue);
                    } else {
                        $query->where($dependency, $dependencyValue);
                    }
                    $field->options($query->pluck($this->getOptionLabel(),  $this->getOptionKey())->toArray());
                } else {
                    // Se não tem valor do campo pai, deixa vazio
                    $field->options([]);
                }
            } else {
                // Se não tem dependência, busca os registros raiz (whereNull)
                if ($fieldUsing = $this->getFieldsUsing()) {
                    $query->whereNull($fieldUsing);
                }
                $field->options($query->pluck($this->getOptionLabel(),  $this->getOptionKey())->toArray());
            }

            $cascadingFields[] = $field;
        }

        return $cascadingFields;
    }

    public function toArray($model = null): array
    {
        $cascadingFields = $this->cascadingFields($model);

        // Converte cada field para array
        $fieldsArray = array_map(function ($field) use ($model) {
            return $field->toArray($model);
        }, $cascadingFields);

        return  array_merge([
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'default' => $this->getDefault(),
            'helpText' => $this->getHelpText(),
            'component' => $this->getComponent(),
            'fieldsUsing' => $this->getFieldsUsing(),
            'fields' => $fieldsArray,
        ], $this->getGridLayoutConfig());
    }
}
