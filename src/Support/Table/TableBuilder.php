<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table;

use Callcocam\LaravelRaptor\Support\Cast\CastRegistry;
use Callcocam\LaravelRaptor\Support\Concerns;
use Callcocam\LaravelRaptor\Support\Concerns\FactoryPattern;
use Callcocam\LaravelRaptor\Support\Table\Sources\ModelSource;

class TableBuilder
{
    use Concerns\Interacts\WithColumns,
        Concerns\Interacts\WithActions,
        Concerns\Interacts\WithBulkActions,
        Concerns\Interacts\WithFilters,
        Concerns\Interacts\WithHeaderActions;
    use FactoryPattern;

    protected $dataSource;


    protected array $config = [
        'auto_detect_casts' => true,
    ];

    public function __construct($model = null, $type = 'model')
    {

        // === INICIALIZAÇÃO DO SISTEMA AUTOMÁTICO COMPLETO ===
        CastRegistry::initialize(); // Carrega formatadores padrão

        $this->dataSource = match ($type) {
            'model' => ModelSource::makeForModel($model, $this->config)->context($this),
            default => $model,
        };

        // ✅ CORREÇÃO: Inicializar após contexto definido
        if ($this->dataSource && method_exists($this->dataSource, 'initialize')) {
            $this->dataSource->initialize();
        }
    }

    public function toArray(): array
    {
        return [
            'columns' => $this->getArrayColumns(),
            'actions' => $this->getArrayActions(),
            'bulkActions' => $this->getArrayBulkActions(),
            'filters' => $this->getArrayFilters(),
            'headerActions' => $this->getArrayHeaderActions(),
        ];
    }
}
