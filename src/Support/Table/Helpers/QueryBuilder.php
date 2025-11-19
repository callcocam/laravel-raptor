<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Helpers;

use Illuminate\Database\Eloquent\Builder;

/**
 * QueryBuilder - Constrói queries Eloquent com filtros, scopes e busca
 *
 * RESPONSABILIDADES:
 * ✅ Pipeline de construção de queries
 * ✅ Aplicação de scopes
 * ✅ Aplicação de filtros (simples e relacionamentos)
 * ✅ Aplicação de busca global
 * ✅ Aplicação de ordenação
 * ✅ Eager loading automático
 */
class QueryBuilder
{
    protected RelationshipHandler $relationshipHandler;

    public function __construct(RelationshipHandler $relationshipHandler)
    {
        $this->relationshipHandler = $relationshipHandler;
    }

    /**
     * Constrói query completa com todos os filtros e otimizações
     */
    public function build(
        Builder $baseQuery,
        array $scopes = [],
        array $filters = [],
        ?string $searchTerm = null,
        array $searchableColumns = [],
        array $orderBy = [],
        array $columns = []
    ): Builder {
        $query = $baseQuery;

        // Pipeline de construção
        $query = $this->applyScopes($query, $scopes);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySearch($query, $searchTerm, $searchableColumns);
        $query = $this->applySorting($query, $orderBy, $columns);
        $query = $this->applyEagerLoading($query, $columns);

        return $query;
    }

    /**
     * Aplica scopes definidos
     */
    protected function applyScopes(Builder $query, array $scopes): Builder
    {
        foreach ($scopes as $scope => $parameters) {
            if (is_numeric($scope)) {
                // Scope sem parâmetros: ->published()
                $query->{$parameters}();
            } else {
                // Scope com parâmetros: ->ofType('admin')
                $query->{$scope}(...(array) $parameters);
            }
        }

        return $query;
    }

    /**
     * Aplica todos os filtros (delega para RelationshipHandler quando necessário)
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter) {
            $filterName = $filter->getName();
            $value = $this->getFilterValue($filter);

            // Pula se valor vazio
            if ($value === null || $value === '') {
                continue;
            }

            // Usa query customizada do filtro se existir
            if ($this->hasCustomFilterQuery($filter)) {
                $filter->setValue($value);
                $filter->applyUserQuery($query);
                continue;
            }

            // Aplica filtro automático (passa o objeto filter para usar strategy)
            $query = $this->applyAutomaticFilter($query, $filterName, $value, $filter);
        }

        return $query;
    }

    /**
     * Aplica filtro automático (usa strategy do filtro se disponível)
     */
    protected function applyAutomaticFilter(Builder $query, string $column, mixed $value, $filter = null): Builder
    {
        if ($this->relationshipHandler->isRelationshipColumn($column)) {
            return $this->relationshipHandler->applyFilter($query, $column, $value, $filter);
        }

        // Usa strategy do filtro se disponível
        if ($filter && method_exists($filter, 'getStrategy')) {
            $strategy = $filter->getStrategy();

            return $strategy->apply($query, $column, $value);
        }

        // Fallback: comportamento legado (like para strings, in para arrays)
        if (is_array($value)) {
            return $query->whereIn($column, $value);
        }

        return $query->where($column, 'like', "%{$value}%");
    }

    /**
     * Aplica busca global nas colunas pesquisáveis
     */
    protected function applySearch(Builder $query, ?string $searchTerm, array $searchableColumns): Builder
    {
        if (! $searchTerm || empty($searchableColumns)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($searchTerm, $searchableColumns) {
            foreach ($searchableColumns as $column) {
                if ($this->relationshipHandler->isRelationshipColumn($column)) {
                    $this->relationshipHandler->applySearch($q, $column, $searchTerm);
                } else {
                    $q->orWhere($column, 'like', "%{$searchTerm}%");
                }
            }
        });
    }

    /**
     * Aplica ordenação (delega para RelationshipHandler quando necessário)
     */
    protected function applySorting(Builder $query, array $orderBy, array $columns): Builder
    {
        foreach ($orderBy as $column => $direction) {
            // Valida se a coluna é sortable
            if (! $this->isColumnSortable($column, $columns)) {
                continue;
            }

            if ($this->relationshipHandler->isRelationshipColumn($column)) {
                $query = $this->relationshipHandler->applySort($query, $column, $direction);
            } else {
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    /**
     * Aplica eager loading automático baseado nas colunas
     */
    protected function applyEagerLoading(Builder $query, array $columns): Builder
    {
        $columnNames = array_map(fn ($col) => $col->getName(), $columns);
        $relations = $this->relationshipHandler->extractRelations($columnNames);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    /**
     * Verifica se filtro tem query customizada
     */
    protected function hasCustomFilterQuery($filter): bool
    {
        return method_exists($filter, 'applyUserQuery') && method_exists($filter, 'setValue');
    }

    /**
     * Obtém valor do filtro (injetado pelo ModelSource via _contextValue)
     */
    protected function getFilterValue($filter): mixed
    {
        return $filter->_contextValue ?? null;
    }

    /**
     * Verifica se uma coluna pode ser ordenada
     */
    protected function isColumnSortable(string $columnName, array $columns): bool
    {
        if (empty($columns)) {
            // Se não há colunas definidas, permite ordenação
            return true;
        }

        foreach ($columns as $column) {
            if ($column->getName() === $columnName) {
                // Se a coluna tem método isSortable, verifica
                if (method_exists($column, 'isSortable')) {
                    return $column->isSortable();
                }

                // Se não tem o método, permite por padrão
                return true;
            }
        }

        // Coluna não encontrada nas definições, permite por padrão
        return true;
    }
}
