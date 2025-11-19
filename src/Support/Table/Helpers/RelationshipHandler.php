<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * RelationshipHandler - Gerencia toda lógica relacionada a relacionamentos Eloquent
 *
 * RESPONSABILIDADES:
 * ✅ Parse de colunas de relacionamento (ex: user.name)
 * ✅ Validação de relacionamentos
 * ✅ Aplicação de filtros em relacionamentos
 * ✅ Aplicação de busca em relacionamentos
 * ✅ Extração de relações para eager loading
 */
class RelationshipHandler
{
    protected string $separator;

    protected Model $model;

    public function __construct(Model $model, string $separator = '.')
    {
        $this->model = $model;
        $this->separator = $separator;
    }

    /**
     * Verifica se uma coluna é de relacionamento (ex: user.name)
     */
    public function isRelationshipColumn(string $column): bool
    {
        return str_contains($column, $this->separator);
    }

    /**
     * Extrai relação e campo de uma coluna
     * Ex: "user.name" -> ["user", "name"]
     * Ex: "user.profile.avatar" -> ["user.profile", "avatar"]
     */
    public function parse(string $column): array
    {
        $parts = explode($this->separator, $column);
        $field = array_pop($parts);
        $relation = implode('.', $parts); // Suporta nested relations

        return [$relation, $field];
    }

    /**
     * Valida se um relacionamento existe no model
     */
    public function isValid(string $relation): bool
    {
        // Suporta nested relations: user.profile
        $parts = explode('.', $relation);
        $currentModel = $this->model;

        foreach ($parts as $relationPart) {
            if (! method_exists($currentModel, $relationPart)) {
                return false;
            }

            try {
                $relationInstance = $currentModel->{$relationPart}();

                if (! $relationInstance instanceof Relation) {
                    return false;
                }

                $currentModel = $relationInstance->getRelated();
            } catch (\Throwable $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Aplica filtro em relacionamento usando whereHas
     * Suporta strategies de filtro
     */
    public function applyFilter(Builder $query, string $column, mixed $value, $filter = null): Builder
    {
        [$relation, $field] = $this->parse($column);

        if (! $this->isValid($relation)) {
            return $query;
        }

        return $query->whereHas($relation, function (Builder $q) use ($field, $value, $filter) {
            // Usa strategy do filtro se disponível
            if ($filter && method_exists($filter, 'getStrategy')) {
                $strategy = $filter->getStrategy();
                $strategy->apply($q, $field, $value);

                return;
            }

            // Fallback: comportamento legado
            if (is_array($value)) {
                $q->whereIn($field, $value);
            } else {
                $q->where($field, 'like', "%{$value}%");
            }
        });
    }

    /**
     * Aplica busca em relacionamento usando orWhereHas
     */
    public function applySearch(Builder $query, string $column, string $searchTerm): Builder
    {
        [$relation, $field] = $this->parse($column);

        if (! $this->isValid($relation)) {
            return $query;
        }

        return $query->orWhereHas($relation, function (Builder $q) use ($field, $searchTerm) {
            $q->where($field, 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Aplica ordenação em relacionamento via JOIN
     */
    public function applySort(Builder $query, string $column, string $direction): Builder
    {
        [$relation, $field] = $this->parse($column);

        if (! $this->isValid($relation)) {
            // Fallback: retorna query sem modificação
            return $query;
        }

        try {
            $relationInstance = $this->model->{$relation}();
            $relatedTable = $relationInstance->getRelated()->getTable();
            $foreignKey = $relationInstance->getForeignKeyName();
            $localKey = $relationInstance->getLocalKeyName();

            // Define alias único para o join
            $joinAlias = "sort_{$relation}";

            // Verifica se join já existe
            if (! $this->hasJoin($query, $joinAlias)) {
                $query->leftJoin(
                    "{$relatedTable} as {$joinAlias}",
                    "{$this->model->getTable()}.{$localKey}",
                    '=',
                    "{$joinAlias}.{$foreignKey}"
                );
            }

            $query->orderBy("{$joinAlias}.{$field}", $direction);

        } catch (\Throwable $e) {
            // Log erro se função disponível
            if (function_exists('logger')) {
                logger()->warning("Failed to sort by relationship: {$column}", [
                    'error' => $e->getMessage(),
                ]);
            }

            // Retorna query sem modificação em caso de erro
        }

        return $query;
    }

    /**
     * Extrai todas as relações de um array de colunas
     */
    public function extractRelations(array $columns): array
    {
        $relations = [];

        foreach ($columns as $column) {
            if ($this->isRelationshipColumn($column)) {
                [$relation] = $this->parse($column);

                if (! in_array($relation, $relations) && $this->isValid($relation)) {
                    $relations[] = $relation;
                }
            }
        }

        return $relations;
    }

    /**
     * Verifica se join já existe na query
     */
    protected function hasJoin(Builder $query, string $alias): bool
    {
        $joins = $query->getQuery()->joins ?? [];

        foreach ($joins as $join) {
            if (str_contains($join->table, $alias)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Define o separador de relacionamentos
     */
    public function setSeparator(string $separator): self
    {
        $this->separator = $separator;

        return $this;
    }
}
