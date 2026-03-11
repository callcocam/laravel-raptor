<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Callcocam\LaravelRaptor\Support\Table\Filters\SelectFilter;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait compartilhado para resolver opções em cascata (hierárquicas).
 * Usado por CascadingField (form) e SelectCascadingFilter (table).
 *
 * Requer no consumidor: getFields(), getFieldsUsing(), getOptionLabel(), getOptionKey(),
 * getCascadingQuery($context), getCascadingQueryCallback(), evaluate(), getName().
 */
trait ResolvesCascadingOptions
{
    /**
     * Resolve as opções de cada nível da cascata.
     * Retorna array de ['name' => string, 'label' => string, 'dependsOn' => ?string, 'options' => array].
     *
     * @param  Model|array<string, mixed>|null  $context  Model (form) ou array/request (filter)
     * @return array<int, array{name: string, label: string, dependsOn: ?string, options: array}>
     */
    protected function resolveCascadingOptionsToArray(Model|array|null $context = null): array
    {
        $fields = $this->getFields();

        if (empty($fields)) {
            return [];
        }

        $callback = $this->getCascadingQueryCallback();
        if ($callback instanceof Closure) {
            $resolved = $this->evaluate($callback, [
                'model' => $context,
                'context' => $context,
                'fields' => $fields,
            ]);

            return $this->normalizeResolvedCascadingFields($resolved, $fields);
        }

        $queryUsing = $this->getCascadingQuery($context);
        if (! $queryUsing instanceof Builder) {
            return $this->fieldsToResolvedArray($fields, []);
        }

        $cascadingData = $this->getCascadingDependencyValues($context);
        $optionLabel = $this->getOptionLabel();
        $optionKey = $this->getOptionKey();
        $fieldsUsing = $this->getFieldsUsing();
        $result = [];

        foreach ($fields as $field) {
            $name = $this->getFieldName($field);
            $label = $this->getFieldLabel($field);
            $dependency = $this->getFieldDependsOn($field);
            if ($field instanceof SelectFilter) {
                $query = $this->cloneQueryForField($field, $queryUsing);

                if ($dependency) {
                    $dependencyValue = request()->query($dependency)
                        ?? $cascadingData[$dependency]
                        ?? null;

                    if ($dependencyValue !== null) {
                        if ($fieldsUsing) {
                            $query->where($fieldsUsing, $dependencyValue);
                        } else {
                            $query->where($dependency, $dependencyValue);
                        }
                        $options = $query->pluck($optionLabel, $optionKey)->toArray();
                    } else {
                        $options = [];
                    }
                } else {
                    if ($fieldsUsing) {
                        $query->whereNull($fieldsUsing);
                    }
                    $options = $query->pluck($optionLabel, $optionKey)->toArray();
                }
            } else {
                $options = $field->getOptions();
            }

            $result[] = [
                'name' => $name,
                'label' => $label,
                'dependsOn' => $dependency,
                'options' => $options,
            ];
        }

        return $result;
    }

    /**
     * Valores das dependências para preencher os níveis (request + context).
     *
     * @return array<string, mixed>
     */
    protected function getCascadingDependencyValues(Model|array|null $context): array
    {
        $values = [];

        foreach ($this->getFields() as $field) {
            $dep = $this->getFieldDependsOn($field);
            if (! $dep) {
                continue;
            }
            $val = request()->query($dep);
            if ($val !== null) {
                $values[$dep] = $val;

                continue;
            }
            if (is_array($context) && array_key_exists($dep, $context)) {
                $values[$dep] = $context[$dep];
            }
            if ($context instanceof Model) {
                $attr = $this->getCascadingContextAttribute();
                $data = $context->{$attr} ?? [];
                if (is_array($data) && isset($data[$dep])) {
                    $values[$dep] = $data[$dep];
                }
            }
        }

        return $values;
    }

    /**
     * Atributo no model/context que guarda os valores da cascata (ex: nome do campo).
     */
    protected function getCascadingContextAttribute(): string
    {
        return $this->getName();
    }

    /**
     * Callback opcional para resolver os níveis customizado (ex: queryUsingCallback no CascadingField).
     */
    protected function getCascadingQueryCallback(): ?Closure
    {
        return null;
    }

    /**
     * Query base para buscar opções (Builder ou null).
     */
    protected function getCascadingQuery(Model|array|null $context): ?Builder
    {
        return null;
    }

    private function getFieldName(mixed $field): string
    {
        if (is_array($field)) {
            return $field['name'] ?? '';
        }

        return $field->getName();
    }

    private function getFieldLabel(mixed $field): string
    {
        if (is_array($field)) {
            return $field['label'] ?? '';
        }
        if (method_exists($field, 'getLabel')) {
            return $field->getLabel() ?? '';
        }

        return '';
    }

    private function getFieldDependsOn(mixed $field): ?string
    {
        if (is_array($field)) {
            return $field['dependsOn'] ?? null;
        }
        if (method_exists($field, 'getDependsOn')) {
            $v = $field->getDependsOn();

            return is_string($v) ? $v : null;
        }

        return null;
    }

    private function cloneQueryForField(mixed $field, Builder $queryUsing): Builder
    {
        if (is_object($field) && method_exists($field, 'getQueryUsingCascading')) {
            $fieldQuery = $field->getQueryUsingCascading();
            if ($fieldQuery instanceof Builder) {
                return clone $fieldQuery;
            }
        }

        return clone $queryUsing;
    }

    /**
     * Converte lista de fields em array resolvido com opções vazias (quando não há query).
     *
     * @param  array<int, mixed>  $fields
     * @param  array<int, array{name: string, label: string, dependsOn: ?string, options: array}>  $withOptions
     * @return array<int, array{name: string, label: string, dependsOn: ?string, options: array}>
     */
    private function fieldsToResolvedArray(array $fields, array $withOptions): array
    {
        $result = [];
        foreach ($fields as $i => $field) {
            $result[] = [
                'name' => $this->getFieldName($field),
                'label' => $this->getFieldLabel($field),
                'dependsOn' => $this->getFieldDependsOn($field),
                'options' => $withOptions[$i] ?? [],
            ];
        }

        return $result;
    }

    /**
     * Normaliza retorno do callback customizado para o formato [name, label, dependsOn, options].
     *
     * @param  mixed  $resolved  Retorno do getCascadingQueryCallback
     * @return array<int, array{name: string, label: string, dependsOn: ?string, options: array}>
     */
    private function normalizeResolvedCascadingFields(mixed $resolved, array $fields): array
    {
        if (! is_array($resolved)) {
            return $this->fieldsToResolvedArray($fields, []);
        }

        $normalized = [];
        foreach ($resolved as $item) {
            if (is_object($item)) {
                $options = method_exists($item, 'getOptions') ? $item->getOptions() : [];
                $normalized[] = [
                    'name' => $item->getName(),
                    'label' => method_exists($item, 'getLabel') ? ($item->getLabel() ?? '') : '',
                    'dependsOn' => method_exists($item, 'getDependsOn') ? ($item->getDependsOn() ?? null) : null,
                    'options' => is_array($options) ? $options : [],
                ];
            } elseif (is_array($item)) {
                $normalized[] = [
                    'name' => $item['name'] ?? '',
                    'label' => $item['label'] ?? '',
                    'dependsOn' => $item['dependsOn'] ?? null,
                    'options' => $item['options'] ?? [],
                ];
            }
        }

        return $normalized;
    }
}
