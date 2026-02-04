<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Exports\DefaultExport;
use Callcocam\LaravelRaptor\Jobs\ProcessExport;
use Callcocam\LaravelRaptor\Notifications\ExportCompletedNotification;
use Callcocam\LaravelRaptor\Events\ExportCompleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

class ExportAction extends ExecuteAction
{
    protected string $method = 'POST';
    protected ?string $modelClass = null;
    protected ?Builder $query = null;
    protected array $exportColumns = [];
    protected bool $useJob = false;
    protected ?string $exportClass = null;
    protected ?string $fileName = null;

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'export');
        $this->name($name)
            ->label('Exportar')
            ->icon('Download')
            ->color('green')
            ->tooltip('Exportar registros')
            ->policy('export')
            ->component('action-confirm')
            ->confirm([
                'title' => 'Exportar Registros',
                'text' => 'Tem certeza que deseja exportar os registros?',
                'confirmButtonText' => 'Sim, Exportar',
                'cancelButtonText' => 'Cancelar',
                'successMessage' => 'Sua exportação foi iniciada e será processada em segundo plano.',
            ])
            ->executeUrlCallback(str($this->name)->replace('export', 'execute')->toString())
            ->callback(function (Request $request, ?Model $model) {
                $user = $request->user();
                $query = $this->getQuery();
                $query = $this->applyFiltersFromRequest($query, $request); // Apply filters

                $exportClass = $this->getExportClass();
                $fileName = $this->getFileName();
                $filePath = 'exports/' . $fileName;
                $resourceName = $this->getResourceName();

                if ($this->shouldUseJob()) {
                    // Extrai os filtros da request para passar ao job
                    $filters = $request->query('filters', []);
                    
                    // Obtém a conexão do modelo
                    $model = app($this->getModelClass());
                    $connectionName = $model->getConnectionName();
                    $connectionConfig = config("database.connections.{$connectionName}");
                    
                    // Envia para fila
                    ProcessExport::dispatch(
                        $this->getModelClass(),
                        $filters,
                        $this->getExportColumns(),
                        $fileName,
                        $filePath,
                        $resourceName,
                        $user->id,
                        $connectionName,
                        $connectionConfig
                    );

                    return [
                        'notification' => [
                            'title' => 'Exportação Iniciada',
                            'text' => 'Sua exportação está sendo processada. Você receberá uma notificação quando estiver pronta para download.',
                            'type' => 'info',
                        ],
                    ];
                }

                try {
                    $export = new $exportClass($query, $this->getExportColumns());
                    Excel::store($export, $filePath);

                    // Obtém o total de linhas exportadas
                    $totalRows = $query->count();

                    // Gera URL de download
                    $downloadUrl = route('download.export', ['filename' => $fileName]);
                    
                    // Para exportação síncrona, cria notificação no banco com link de download
                    $user->notify(new ExportCompletedNotification($fileName, $downloadUrl, $resourceName));

                    // Dispara evento de broadcast para atualização em tempo real
                    event(new ExportCompleted(
                        userId: $user->id,
                        modelName: class_basename($this->getModelClass()),
                        totalRows: $totalRows,
                        filePath: $filePath,
                        fileName: $fileName
                    ));

                    return [
                        'notification' => [
                            'title' => 'Exportação Concluída',
                            'text' => 'Seu arquivo está pronto para download. Verifique suas notificações.',
                            'type' => 'success',
                        ],
                    ];
                } catch (\Exception $e) {
                    report($e);
                    return [
                        'notification' => [
                            'title' => 'Erro na Exportação',
                            'text' => 'Ocorreu um erro ao gerar o arquivo de exportação.',
                            'type' => 'error',
                        ],
                    ];
                }
            });
        $this->setUp();
    }

    /**
     * Apply filters from the request to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFiltersFromRequest(\Illuminate\Database\Eloquent\Builder $query, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $filters = $request->query('filters', []);

        if (is_array($filters)) {
            foreach ($filters as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } elseif (!empty($value)) {
                    $query->where($column, 'like', "%{$value}%");
                }
            }
        }

        return $query;
    }

    public function model(string $modelClass): self
    {
        $this->modelClass = $modelClass;
        $this->query = null; // Reset query if model is set
        return $this;
    }
 

    public function query(Builder $query): self
    {
        $this->query = $query;
        $this->modelClass = null; // Reset modelClass if query is set
        return $this;
    }

    public function getQuery(): Builder
    {
        return $this->query ?? $this->modelClass::query();
    }

    public function getModelClass(): string
    {
        if ($this->modelClass) {
            return $this->modelClass;
        }

        if ($this->query) {
            return get_class($this->query->getModel());
        }

        throw new \Exception('Model class ou query não foi definido para a exportação.');
    }

    public function exportColumns(array $columns): self
    {
        $this->exportColumns = $columns;
        return $this;
    }

    public function getExportColumns(): array
    {
        if (empty($this->exportColumns)) {
            // Se as colunas não forem definidas, tente obtê-las do modelo
            $model = $this->getModelClass();
            if ($model && method_exists($model, 'getTableColumns')) {
                return $model::getTableColumns();
            }
        }
        return $this->exportColumns;
    }

    public function useJob(bool $useJob = true): self
    {
        $this->useJob = $useJob;
        return $this;
    }

    public function shouldUseJob(): bool
    {
        return $this->useJob;
    }

    public function export(string $exportClass): self
    {
        $this->exportClass = $exportClass;
        return $this;
    }

    public function getExportClass(): string
    {
        return $this->exportClass ?? DefaultExport::class;
    }

    public function fileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileName(): string
    {
        if ($this->fileName) {
            return $this->fileName;
        }

        $modelName = class_basename($this->getModelClass());
        return sprintf('%s-%s.xlsx', Str::kebab($modelName), now()->format('Y-m-d-H-i-s'));
    }

    protected function getResourceName(): string
    {
        $modelName = class_basename($this->getModelClass());
        return Str::plural(Str::lower(str_replace('_', ' ', Str::snake($modelName))));
    }
}
