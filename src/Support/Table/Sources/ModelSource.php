<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Sources;

use Callcocam\LaravelRaptor\Support\Table\Helpers\QueryBuilder;
use Callcocam\LaravelRaptor\Support\Table\Helpers\RelationshipHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ModelSource - Fonte de dados Eloquent ORM (Refatorado)
 *
 * RESPONSABILIDADES (foco em ORQUESTRAÇÃO):
 * ✅ Coordenar construção de queries via QueryBuilder
 * ✅ Gerenciar relacionamentos via RelationshipHandler
 * ✅ Extrair configuração das colunas
 * ✅ Retornar dados paginados
 *
 * DELEGAÇÃO:
 * ➡️ QueryBuilder - Construção de queries, filtros, busca
 * ➡️ RelationshipHandler - Lógica de relacionamentos
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

    protected RelationshipHandler $relationshipHandler;

    protected QueryBuilder $queryBuilder;

    public static function makeForQuery($query, array $config = []): static
    {
        $instance = new static($query->getModel(), $config);
        $instance->baseQuery($query);

        return $instance;
    }

    /**
     * Inicialização - extrai colunas pesquisáveis do contexto
     */
    public function initialize(): void
    {
        parent::initialize();

        // Inicializa helpers
        $this->relationshipHandler = new RelationshipHandler(
            $this->getModelInstance(),
            $this->getConfig('relationship_separator')
        );

        $this->queryBuilder = new QueryBuilder($this->relationshipHandler);

        $this->extractSearchableColumns();
    }

    /**
     * Retorna dados paginados (com casts do Eloquent aplicados)
     */
    public function getData(): LengthAwarePaginator
    {
        $query = $this->buildQuery();

        // Retorna paginação - Eloquent aplica casts automaticamente
        return $query
            ->paginate($this->perPage, ['*'], 'page', $this->page)->onEachSide(2);
    }

    /**
     * Constrói a query com todos os filtros, busca e ordenação
     * DELEGADA para QueryBuilder
     */
    protected function buildQuery(): Builder
    {
        $baseQuery = $this->getBaseQuery();

        // Detecta colunas searchable e sortable automaticamente
        $this->detectSearchableAndSortableColumns();

        // Delega construção completa para QueryBuilder
        return $this->queryBuilder->build(
            baseQuery: $baseQuery,
            scopes: $this->getScopes(),
            filters: $this->getFiltersWithContext(),
            searchTerm: $this->searchTerm,
            searchableColumns: $this->searchableColumns,
            orderBy: $this->orderBy,
            columns: $this->getColumns()
        );
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
        }
    }

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
     * Obtém filtros com valores do contexto injetados
     */
    protected function getFiltersWithContext(): array
    {
        $filters = $this->getFilters();

        foreach ($filters as $filter) {
            $value = $this->getFilterContextValue($filter);
            if ($value !== null && $value !== '' && (! is_array($value) || $value !== [])) {
                $filter->_contextValue = $value;
            }
        }

        return $filters;
    }

    /**
     * Obtém o valor do request para aplicar no filtro.
     *
     * Comportamento por tipo de filtro:
     *
     * 1) Filtro normal: valor vem em request[filter->getName()].
     *
     * 2) Filtro em cascata (SelectCascadingFilter):
     *    - Na URL vêm os níveis: segmento_varejista, departamento, ..., subsegmento (não o nome do filtro).
     *    - Modo "só último nível" (default): retorna o valor do ÚLTIMO nível selecionado (ex.: subsegmento).
     *      Aplica where(column, value) — produtos daquela categoria exata.
     *    - Modo "incluir pais": ativado quando request[filter->getIncludeParentsParam()] é truthy.
     *      Retorna ARRAY com todos os níveis selecionados (do primeiro ao último).
     *      Aplica whereIn(column, values) — produtos da categoria ou de qualquer pai no caminho.
     */
    protected function getFilterContextValue($filter): mixed
    {
        $context = $this->getContext();
        if ($context === null) {
            return null;
        }

        if (! method_exists($filter, 'getFields') || ! method_exists($filter, 'hasFields') || ! $filter->hasFields()) {
            return $context->getRequestValue($filter->getName());
        }

        $fields = $filter->getFields();
        // Filtro pode definir só os campos que são "níveis" (IDs), ex. excluindo TernaryFilter
        $fieldNames = method_exists($filter, 'getLevelFieldNames') && $filter->getLevelFieldNames() !== null
            ? $filter->getLevelFieldNames()
            : $this->extractFieldNames($fields);

        if ($fieldNames === []) {
            return null;
        }

        // Modo "incluir pais": frontend envia param ex. category_id_include_parents=1
        $includeParentsParam = method_exists($filter, 'getIncludeParentsParam') ? $filter->getIncludeParentsParam() : null;
        $includeParents = $includeParentsParam !== null
            && $context->getRequestValue($includeParentsParam);

        if ($includeParents) {
            // Coleta todos os valores selecionados (do primeiro ao último nível) para whereIn()
            $values = [];
            foreach ($fieldNames as $name) {
                $v = $context->getRequestValue($name);
                if ($v !== null && $v !== '') {
                    $values[] = $v;
                }
            }

            return $values !== [] ? $values : null;
        }

        // Modo "só último nível": retorna apenas o último valor preenchido (comportamento padrão)
        for ($i = count($fieldNames) - 1; $i >= 0; $i--) {
            $value = $context->getRequestValue($fieldNames[$i]);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Extrai nomes dos campos do filtro em cascata (objects com getName() ou arrays com 'name').
     *
     * @return array<int, string>
     */
    protected function extractFieldNames(array $fields): array
    {
        $names = [];
        foreach ($fields as $field) {
            $name = is_object($field) && method_exists($field, 'getName')
                ? $field->getName()
                : (is_array($field) ? ($field['name'] ?? null) : null);
            if ($name !== null) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Obtém query base (customizada ou nova)
     */
    protected function getBaseQuery(): Builder
    {
        return $this->baseQuery ?? $this->getModelInstance()->newQuery();
    }

    /**
     * Obtém instância do model
     */
    protected function getModelInstance(): Model
    {
        if ($this->model instanceof Model) {
            return $this->model;
        }

        return is_string($this->model) ? app($this->model) : $this->model;
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
}
