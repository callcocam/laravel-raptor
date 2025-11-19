<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Sources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ModelSource - Fonte de dados Eloquent ORM
 *
 * RESPONSABILIDADES (foco em DADOS):
 * ✅ Construir queries Eloquent
 * ✅ Aplicar filtros (simples e relacionamentos)
 * ✅ Aplicar busca global (searchable columns)
 * ✅ Aplicar ordenação (com suporte a relacionamentos)
 * ✅ Otimizar queries (eager loading automático)
 * ✅ Retornar dados paginados
 *
 * FORMATAÇÃO:
 * ℹ️ Usa APENAS os casts do Eloquent Model
 * ℹ️ Não faz transformação de apresentação
 * ℹ️ Retorna models/collections como vieram do banco
 */
class ModelSource extends AbstractSource
{
    protected ?Builder $baseQuery = null;

    protected array $searchableColumns = [];

    /**
     * Inicialização - extrai colunas pesquisáveis do contexto
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->extractSearchableColumns();
    }

    /**
     * Retorna dados paginados (com casts do Eloquent aplicados)
     */
    public function getData(): LengthAwarePaginator
    {
        $query = $this->buildQuery();

        // Retorna paginação - Eloquent aplica casts automaticamente
        return $query->paginate($this->perPage, ['*'], 'page', $this->page);
    }

    /**
     * Constrói a query com todos os filtros, busca e ordenação
     */
    protected function buildQuery(): Builder
    {
        $query = $this->getBaseQuery();

        // Detecta colunas searchable e sortable automaticamente
        $this->detectSearchableAndSortableColumns();

        // Pipeline de construção da query
        $query = $this->applyScopes($query);
        $query = $this->applyFilters($query);
        $query = $this->applySearch($query);
        $query = $this->applySorting($query);
        $query = $this->applyEagerLoading($query);

        return $query;
    }

    /**
     * Detecta automaticamente colunas searchable e sortable do contexto
     */
    protected function detectSearchableAndSortableColumns(): void
    {
        $columns = $this->getColumns();

        if (empty($columns)) {
            return;
        }

        foreach ($columns as $column) {
            $columnName = $column->getName();

            // Detecta colunas searchable
            if (method_exists($column, 'isSearchable') && $column->isSearchable()) {
                if (! in_array($columnName, $this->searchableColumns)) {
                    $this->searchableColumns[] = $columnName;
                }
            }

            // Detecta colunas sortable
            // (não altera $orderBy se já foi definido pelo request)
            if (method_exists($column, 'isSortable') && $column->isSortable()) {
                // Apenas registra que a coluna é sortable
                // A ordenação vem do request ou usa default
            }
        }
    }

    /**
     * Obtém query base (customizada ou nova)
     */
    protected function getBaseQuery(): Builder
    {
        return $this->baseQuery ?? $this->getModelInstance()->newQuery();
    }

    /**
     * Aplica scopes definidos no contexto
     */
    protected function applyScopes(Builder $query): Builder
    {
        foreach ($this->getScopes() as $scope => $parameters) {
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
     * Aplica filtros (suporta relacionamentos)
     */
    protected function applyFilters(Builder $query): Builder
    {

        foreach ($this->getFilters() as $filter) {
            $filterName = $filter->getName();
            $value = $this->getContext()?->getRequestValue($filterName);

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

            // Aplica filtro automático
            $query = $this->applyAutomaticFilter($query, $filterName, $value);
        }

        return $query;
    }

    /**
     * Aplica busca global nas colunas pesquisáveis
     */
    protected function applySearch(Builder $query): Builder
    {
        if (! $this->searchTerm || empty($this->searchableColumns)) {
            return $query;
        }

        return $query->where(function (Builder $q) {
            foreach ($this->searchableColumns as $column) {
                if ($this->isRelationshipColumn($column)) {
                    $this->addRelationshipSearch($q, $column);
                } else {
                    $q->orWhere($column, 'like', "%{$this->searchTerm}%");
                }
            }
        });
    }

    /**
     * Aplica ordenação (suporta relacionamentos)
     * Valida se a coluna é sortable antes de aplicar
     */
    protected function applySorting(Builder $query): Builder
    {
        foreach ($this->orderBy as $column => $direction) {
            // Valida se a coluna é sortable
            if (! $this->isColumnSortable($column)) {
                continue;
            }

            if ($this->isRelationshipColumn($column)) {
                $query = $this->applyRelationshipSort($query, $column, $direction);
            } else {
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    /**
     * Verifica se uma coluna pode ser ordenada
     */
    protected function isColumnSortable(string $columnName): bool
    {
        $columns = $this->getColumns();

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

    /**
     * Aplica eager loading automático baseado nas colunas
     */
    protected function applyEagerLoading(Builder $query): Builder
    {
        $relations = $this->getRelationsFromColumns();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    /**
     * ========================================
     * HELPERS - Relacionamentos
     * ========================================
     */

    /**
     * Verifica se coluna é de relacionamento (ex: user.name)
     */
    protected function isRelationshipColumn(string $column): bool
    {
        return str_contains($column, $this->getConfig('relationship_separator'));
    }

    /**
     * Extrai relação e campo de coluna (ex: user.name -> ['user', 'name'])
     */
    protected function parseRelationshipColumn(string $column): array
    {
        $parts = explode($this->getConfig('relationship_separator'), $column);
        $field = array_pop($parts);
        $relation = implode('.', $parts); // Suporta nested: user.profile.avatar

        return [$relation, $field];
    }

    /**
     * Valida se relacionamento existe no model
     */
    protected function isValidRelationship(string $relation): bool
    {
        $model = $this->getModelInstance();

        if (! method_exists($model, $relation)) {
            return false;
        }

        try {
            $instance = $model->{$relation}();

            return $instance instanceof \Illuminate\Database\Eloquent\Relations\Relation;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Extrai todas as relações das colunas definidas
     */
    protected function getRelationsFromColumns(): array
    {
        $relations = [];

        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();

            if ($this->isRelationshipColumn($columnName)) {
                [$relation] = $this->parseRelationshipColumn($columnName);

                if (! in_array($relation, $relations) && $this->isValidRelationship($relation)) {
                    $relations[] = $relation;
                }
            }
        }

        return $relations;
    }

    /**
     * ========================================
     * HELPERS - Filtros
     * ========================================
     */

    /**
     * Verifica se filtro tem query customizada
     */
    protected function hasCustomFilterQuery($filter): bool
    {
        return method_exists($filter, 'applyUserQuery') && method_exists($filter, 'setValue');
    }

    /**
     * Aplica filtro automático (simples ou relacionamento)
     */
    protected function applyAutomaticFilter(Builder $query, string $column, $value): Builder
    {
        if ($this->isRelationshipColumn($column)) {
            return $this->applyRelationshipFilter($query, $column, $value);
        }

        return $this->applySimpleFilter($query, $column, $value);
    }

    /**
     * Aplica filtro simples
     */
    protected function applySimpleFilter(Builder $query, string $column, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn($column, $value);
        }

        // Busca parcial por padrão
        return $query->where($column, 'like', "%{$value}%");
    }

    /**
     * Aplica filtro em relacionamento
     */
    protected function applyRelationshipFilter(Builder $query, string $column, $value): Builder
    {
        [$relation, $field] = $this->parseRelationshipColumn($column);

        if (! $this->isValidRelationship($relation)) {
            return $query;
        }

        return $query->whereHas($relation, function (Builder $q) use ($field, $value) {
            return $this->applySimpleFilter($q, $field, $value);
        });
    }

    /**
     * ========================================
     * HELPERS - Busca
     * ========================================
     */

    /**
     * Extrai colunas marcadas como searchable
     */
    protected function extractSearchableColumns(): void
    {
        foreach ($this->getColumns() as $column) {
            if (method_exists($column, 'isSearchable') && $column->isSearchable()) {
                $this->searchableColumns[] = $column->getName();
            }
        }
    }

    /**
     * Adiciona busca em relacionamento
     */
    protected function addRelationshipSearch(Builder $query, string $column): void
    {
        [$relation, $field] = $this->parseRelationshipColumn($column);

        if (! $this->isValidRelationship($relation)) {
            return;
        }

        $query->orWhereHas($relation, function (Builder $q) use ($field) {
            $q->where($field, 'like', "%{$this->searchTerm}%");
        });
    }

    /**
     * ========================================
     * HELPERS - Ordenação
     * ========================================
     */

    /**
     * Aplica ordenação em relacionamento via JOIN
     */
    protected function applyRelationshipSort(Builder $query, string $column, string $direction): Builder
    {
        [$relation, $field] = $this->parseRelationshipColumn($column);

        if (! $this->isValidRelationship($relation)) {
            // Fallback: usa ordenação padrão
            return $query->orderBy($this->getConfig('default_order_column'), $direction);
        }

        try {
            $model = $query->getModel();
            $relationInstance = $model->{$relation}();
            $relatedTable = $relationInstance->getRelated()->getTable();
            $foreignKey = $relationInstance->getForeignKeyName();
            $localKey = $relationInstance->getLocalKeyName();

            // Define alias único para o join
            $joinAlias = "sort_{$relation}";

            // Verifica se join já existe
            if (! $this->hasJoin($query, $joinAlias)) {
                $query->leftJoin(
                    "{$relatedTable} as {$joinAlias}",
                    "{$model->getTable()}.{$localKey}",
                    '=',
                    "{$joinAlias}.{$foreignKey}"
                );
            }

            $query->orderBy("{$joinAlias}.{$field}", $direction);

        } catch (\Throwable $e) {
            // Log erro e usa fallback
            if (function_exists('logger')) {
                logger()->warning("Failed to sort by relationship: {$column}", [
                    'error' => $e->getMessage(),
                ]);
            }

            // Fallback: ordenação padrão
            $query->orderBy($this->getConfig('default_order_column'), $direction);
        }

        return $query;
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
     * ========================================
     * HELPERS - Model
     * ========================================
     */

    /**
     * Obtém instância do model
     */
    protected function getModelInstance(): Model
    {
        if ($this->model instanceof Model) {
            return $this->model;
        }

        return is_string($this->model) ? new $this->model : new $this->model;
    }

    /**
     * ========================================
     * PUBLIC API
     * ========================================
     */

    /**
     * Define query base customizada
     */
    public function baseQuery(Builder $query): self
    {
        $this->baseQuery = $query;

        return $this;
    }

    /**
     * Adiciona coluna como pesquisável
     */
    public function addSearchableColumn(string $column): self
    {
        if (! in_array($column, $this->searchableColumns)) {
            $this->searchableColumns[] = $column;
        }

        return $this;
    }

    // ==========================================
    // DEPRECATED - Stub para compatibilidade
    // ==========================================

    /**
     * @deprecated Não usado - formatação via Eloquent casts
     *
     * @internal Stub para silenciar erro de IDE/Intelephense
     */
    protected function getColumnByNameFormat(string $name)
    {
        // Não implementado - formatação delegada ao Eloquent
        return null;
    }
}
