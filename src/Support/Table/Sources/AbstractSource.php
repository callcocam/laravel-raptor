<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Sources;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToContext;
use Callcocam\LaravelRaptor\Support\Concerns\FactoryPattern;
use Illuminate\Database\Eloquent\Model;

/**
 * AbstractSource - Classe base para fontes de dados
 *
 * RESPONSABILIDADES (foco ÚNICO em dados):
 * ✅ Buscar dados da fonte
 * ✅ Aplicar filtros
 * ✅ Aplicar ordenação
 * ✅ Aplicar paginação
 * ✅ Aplicar busca/pesquisa
 * ✅ Otimizar queries (eager loading)
 *
 * NÃO É responsável por:
 * ❌ Formatação de valores (usa casts do Eloquent)
 * ❌ Transformação de apresentação
 * ❌ Lógica de negócio
 */
abstract class AbstractSource
{
    use BelongsToContext;
    use FactoryPattern;

    protected array $config = [];

    protected $model = null;

    // Parâmetros de busca e filtro
    protected int $perPage = 15;

    protected int $page = 1;

    protected ?string $searchTerm = null;

    protected array $orderBy = [];

    protected array $scopes = [];

    /**
     * Construtor
     */
    public function __construct($model = null, array $config = [])
    {
        $this->model = $model instanceof string ? app($model) : $model;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Factory method para criar source a partir de um model
     */
    public static function makeForModel(Model|string|null $model = null, array $config = []): static
    {
        return new static($model, $config);
    }

    /**
     * Configuração padrão
     */
    protected function getDefaultConfig(): array
    {
        return [
            'relationship_separator' => '.', // Ex: user.name
            'default_order_column' => 'id',
            'default_order_direction' => 'desc',
        ];
    }

    /**
     * Inicializa source com dados do contexto
     * Extrai parâmetros de busca, filtros, ordenação do request
     */
    public function initialize(): void
    {
        $context = $this->getContext();

        if (! $context) {
            return;
        }

        // Busca/Search
        $this->searchTerm = $context->getSearch();

        // Ordenação
        $orderBy = $context->getOrderBy();
        $this->orderBy = ! empty($orderBy) ? $orderBy : [
            $this->config['default_order_column'] => $this->config['default_order_direction'],
        ];

        // Scopes
        $this->scopes = $context->getScopes() ?? [];

        // Paginação do request
        if (method_exists($context, 'getRequestValue')) {
            $this->page = (int) $context->getRequestValue('page', 1);
            $this->perPage = (int) $context->getRequestValue('per_page', $this->perPage);
        }
    }

    /**
     * Método principal - retorna dados paginados
     * Cada implementação define como buscar os dados
     */
    abstract public function getData();

    /**
     * Helpers para acessar informações do contexto
     */
    protected function getColumns(): array
    {
        return $this->getContext()?->getColumns() ?? [];
    }

    protected function getFilters(): array
    {
        return $this->getContext()?->getFilters() ?? [];
    }

    protected function getActions(): array
    {
        return $this->getContext()?->getActions() ?? [];
    }

    protected function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Getters/Setters
     */
    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model): self
    {
        $this->model = $model;

        return $this;
    }

    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function setSearchTerm(?string $term): self
    {
        $this->searchTerm = $term;

        return $this;
    }

    public function setOrderBy(array $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getConfig(?string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? null;
    }
}
