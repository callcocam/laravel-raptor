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

        // Injeta valores do contexto em cada filtro
        foreach ($filters as $filter) {
            $filterName = $filter->getName();
            $value = $this->getContext()?->getRequestValue($filterName);

            if ($value !== null && $value !== '') {
                // Adiciona propriedade temporária para QueryBuilder acessar
                $filter->_contextValue = $value;
            }
        }

        return $filters;
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
}
