<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table;

use Callcocam\LaravelRaptor\Support\Cast\CastRegistry;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithTable;
use Callcocam\LaravelRaptor\Support\Table\Concerns\HasSearch;
use Callcocam\LaravelRaptor\Support\Table\Concerns\HasSorting;
use Callcocam\LaravelRaptor\Support\Table\Sources\ModelSource;

class TableBuilder
{
    use WithTable;
    use HasSearch;
    use HasSorting;
    
    protected $dataSource;

    protected bool $dependenciesInjected = false;

    protected array $config = [
        'auto_detect_casts' => true,
    ];

    public function __construct($model = null, $type = 'model')
    {
        // === INICIALIZAÇÃO DO SISTEMA AUTOMÁTICO COMPLETO ===
        CastRegistry::initialize(); // Carrega formatadores padrão

        $this->dataSource = match ($type) {
            'model' => $this->createModelSource($model),
            default => $model,
        };
    }

    /**
     * Cria ModelSource (injeção de dependências será feita lazy)
     */
    protected function createModelSource($model)
    {
        return ModelSource::makeForModel($model, $this->config)
            ->context($this); // Mantém contexto apenas para request params
    }

    /**
     * Injeta dependências lazy (apenas quando necessário)
     * Evita redundância de buscar do contexto o que o TableBuilder já tem
     */
    protected function ensureDependenciesInjected(): void
    {
        if ($this->dependenciesInjected || ! $this->dataSource) {
            return;
        }

        if (method_exists($this->dataSource, 'setColumns')) {
            // ✅ DEPENDENCY INJECTION: Injeta coleções diretamente
            $this->dataSource
                ->setColumns($this->getColumns())
                ->setFilters($this->getFilters())
                ->setActions($this->getActions());

            $this->dependenciesInjected = true;

            // Inicializa após injeção
            if (method_exists($this->dataSource, 'initialize')) {
                $this->dataSource->initialize();
            }
        }
    }

    /**
     * Método público para acessar dataSource (com lazy injection)
     */
    public function getDataSource()
    {
        $this->ensureDependenciesInjected();

        return $this->dataSource;
    }

    /**
     * Força reinjeção de dependências (útil após modificações)
     */
    public function refreshDataSource(): self
    {
        $this->dependenciesInjected = false;
        $this->ensureDependenciesInjected();

        return $this;
    }


    /**
     * Obtem os scope de relacionamento
     */
    public function getScopes(): array
    {
        return [];
    }

    /**
     * 🔄 CORREÇÃO: Métodos que o AbstractSource precisa para paginação/ordenação
     */
    public function getOrderBy(): array
    {
        $request = $this->getRequest();

        if (! $request) {
            return ['id' => 'desc']; // Default fallback
        }

        // ✅ Processar sort e direction da request
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'asc');

        if ($sort) {
            return [$sort => strtolower($direction)];
        }

        return ['id' => 'desc']; // Default se não tiver sort
    }

    public function getSearch(): ?string
    {
        $request = $this->getRequest();

        return $request?->input('search');
    }
}
