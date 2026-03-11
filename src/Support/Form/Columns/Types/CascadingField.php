<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToFields;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\ResolvesCascadingOptions;
use Callcocam\LaravelRaptor\Support\Form\Columns\Column;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class CascadingField extends Column
{
    use BelongsToFields;
    use ResolvesCascadingOptions;

    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected bool $searchable = false;

    /** Query para alimentar as opções dos níveis (diferente de queryUsing do Column). */
    protected Closure|string|Builder|\Illuminate\Database\Query\Builder|null $queryUsingCascading = null;

    protected ?Closure $queryUsingCallback = null;

    protected bool $preserveScroll = true;

    protected bool $preserveState = true;

    protected array $onlyProps = [];

    protected string $fieldLevelName = 'level_name';

    protected string $fieldLevelNivel = 'nivel';

    protected array $fieldLevelNames = [];

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-cascading');
        $this->setUp();
        $this->valueUsing(function ($data, $model) {
            $currentValue = data_get($data, $this->getName(), []);
            $levelNames = [];
            foreach ($this->getFields() as $field) {
                $levelNames[] = $field->getName();
            }
            // Se não for array, converte para array vazio
            if (! is_array($currentValue)) {
                $currentValue = [];
            }

            // Pega o último valor não vazio da hierarquia
            $lastValue = null;
            $lastLevelName = null;
            $lastLevelNivel = null;
            $lastLevelPosition = 1;
            foreach ($currentValue as $key => $value) {
                if (! empty($value)) {
                    $lastValue = $value;
                    $lastLevelName = $levelNames[$lastLevelPosition] ?? null;
                    $lastLevelNivel = ++$lastLevelPosition;
                }
            }

            // Adiciona o último valor selecionado no campo fieldsUsing
            if ($lastValue) {
                return [
                    $this->getFieldsUsing() => $lastValue,
                    $this->getName() => $currentValue,
                    $this->fieldLevelName => $lastLevelName,
                    $this->fieldLevelNivel => $lastLevelNivel,
                ];
            }

            return [
                $this->getName() => $currentValue,
                $this->fieldLevelName => $lastLevelName,
                $this->fieldLevelNivel => $lastLevelNivel,
            ];
        });
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Query para alimentar as opções dos níveis da cascata (não confundir com queryUsing do Column).
     */
    public function queryUsingCascading(Closure|string|Builder|\Illuminate\Database\Query\Builder|null $query): self
    {
        $this->queryUsingCascading = $query;

        return $this;
    }

    public function getQueryUsingCascading(): Builder|\Illuminate\Database\Query\Builder|null
    {
        if ($this->queryUsingCascading !== null) {
            if (is_string($this->queryUsingCascading)) {
                return app($this->queryUsingCascading);
            }
            if ($this->queryUsingCascading instanceof Builder || $this->queryUsingCascading instanceof \Illuminate\Database\Query\Builder) {
                return $this->queryUsingCascading;
            }

            $result = $this->evaluate($this->queryUsingCascading);

            return $result instanceof Builder || $result instanceof \Illuminate\Database\Query\Builder ? $result : null;
        }

        $q = $this->getQueryUsing();

        return $q instanceof Builder || $q instanceof \Illuminate\Database\Query\Builder ? $q : null;
    }

    public function queryUsingCallback(?Closure $callback): self
    {
        $this->queryUsingCallback = $callback;

        return $this;
    }

    public function preserveScroll(bool $preserve = true): self
    {
        $this->preserveScroll = $preserve;

        return $this;
    }

    public function preserveState(bool $preserve = true): self
    {
        $this->preserveState = $preserve;

        return $this;
    }

    public function only(array $props): self
    {
        $this->onlyProps = $props;

        return $this;
    }

    protected function getCascadingQueryCallback(): ?Closure
    {
        return $this->queryUsingCallback;
    }

    protected function getCascadingQuery(mixed $context): ?Builder
    {
        $q = $this->getQueryUsingCascading();

        return $q instanceof Builder ? $q : null;
    }

    protected function cascadingFields($model = null): array
    {
        $fields = $this->getFields();

        foreach ($fields as $field) {
            $this->fieldLevelNames[] = $field->getName();
        }

        $resolved = $this->resolveCascadingOptionsToArray($model);
        $byName = collect($resolved)->keyBy('name');
        $cascadingFields = [];

        foreach ($fields as $field) {
            $item = $byName->get($field->getName());
            if ($item && method_exists($field, 'options')) {
                $field->options($item['options'] ?? []);
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

        return array_merge([
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'default' => $this->getDefault(),
            'helpText' => $this->getHelpText(),
            'component' => $this->getComponent(),
            'fieldsUsing' => $this->getFieldsUsing(),
            'fields' => $fieldsArray,
            'inertia' => [
                'preserveScroll' => $this->preserveScroll,
                'preserveState' => $this->preserveState,
                'only' => $this->onlyProps,
            ],
        ], $this->getGridLayoutConfig());
    }
}
