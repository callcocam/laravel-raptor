<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\Concerns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait WithTable
{

    use Concerns\Interacts\WithColumns,
        Concerns\Interacts\WithActions, 
        Concerns\Interacts\WithBulkActions,
        Concerns\Interacts\WithFilters,
        Concerns\Interacts\WithHeaderActions; 
    use Concerns\Shared\BelongToRequest;
    use Concerns\EvaluatesClosures;
    use Concerns\FactoryPattern;
    // use HasSearch;

    public function toArray(): array
    {
        // Inicializa o data source

        if (method_exists($this->dataSource, 'detectModelConfiguration')) {
            $this->dataSource->detectModelConfiguration();
        }

        $this->dataSource->initialize();

        $this->data = $this->dataSource->getData();
        // Aplica formatação se configurado
        if ($this->config['auto_detect_casts']) {
            $this->data->getCollection()->transform(function ($item) {
                return $this->applyItemFormatting($item);
            });
        }

        // Processa os dados
        $result = $this->processTableData();

        // Adiciona configurações da tabela
        $result = array_merge($result, [
            'columns' => $this->getArrayColumns(),
            'bulkActions' => $this->getArrayBulkActions(), // Adiciona bulk actions
            'filters' => $this->getArrayFilters(),
            'headerActions' => $this->getArrayHeaderActions(),
            // 'search' => $this->getSearch(),
            'isSearcheable' => $this->isSearcheable(), 
            'hasBulkActions' => $this->hasBulkActions(), // Indica se tem bulk actions
            'queryParams' => $this->getQueryParams(), 
        ]);

        return $result;
    }

    /**
     * Processa os dados da tabela baseado no tipo
     */
    protected function processTableData(): array
    {
        // Se data é null ou vazio
        if (empty($this->data)) {
            return [
                'data' => [],
                'pagination' => null,
                'meta' => [],
            ];
        }

        // Se é array simples, assume que já está processado
        if (is_array($this->data)) {
            return $this->data;
        }

        // Se é uma coleção paginada (Laravel Paginator)
        if ($this->data instanceof LengthAwarePaginator) {
            return [
                'data' => $this->data->items(),
                'meta' => [
                    'current_page' => $this->data->currentPage(),
                    'last_page' => $this->data->lastPage(),
                    'per_page' => $this->data->perPage(),
                    'total' => $this->data->total(),
                    'from' => $this->data->firstItem(),
                    'to' => $this->data->lastItem(),
                    'path' => $this->data->path(),
                    'has_more_pages' => $this->data->hasMorePages(),
                    'links' => $this->data->linkCollection(),
                ],
            ];
        }

        // Se é uma Collection simples
        if ($this->data instanceof Collection) {
            return [
                'data' => $this->data->toArray(),
                'meta' => [
                    'total' => $this->data->count(),
                    'count' => $this->data->count(),
                ],
            ];
        }

        // Fallback para outros tipos
        return [
            'data' => [],
            'pagination' => null,
            'meta' => [],
        ];
    }

    /**
     * Executa bulk action via table
     */
    public function handleTableBulkAction(string $actionName, array $selectedIds, array $data = []): array
    {
        return $this->executeBulkAction($actionName, $selectedIds, $data);
    }

    protected function getQueryParams(): array
    {
        return $this->getRequest()->query();
    }

    protected function applyItemFormatting($item)
    {
        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();
            if ($value = data_get($item, $columnName)) { 
                $formattedColumn = $column->render($value, $item);
                $item[$columnName . '_formatted'] = data_get($formattedColumn, 'text', $value);
            }
        }
        $item->actions = $this->evaluateActionsAuthorization($item);

        return $item->toArray();
    }

    /**
     * Avalia quais actions o usuário pode executar neste registro
     * Delega para a própria action a responsabilidade de renderização e validação
     * 
     * A visibilidade é controlada por cada action via BelongsToVisible trait:
     * - ->policy('update') - Usa Laravel Policy
     * - ->visible(fn($item) => ...) - Callback customizado
     * - ->visibleWhen(fn($item, $user) => ...) - Validação complexa
     */
    protected function evaluateActionsAuthorization(Model $model): array
    {
        $actions = [];

        foreach ($this->getActions() as $action) {
            // A action é responsável por sua própria renderização completa
            $rendered = $action->render($model, $this->getRequest()); 
            // Filtra apenas actions visíveis e válidas
            if ($rendered !== null && ($rendered['visible'] ?? true)) {
                $actions[$action->getName()] = $rendered;
            }
        }

        return $actions;
    }
}
