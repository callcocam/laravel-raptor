<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Exports\DefaultExport;
use Callcocam\LaravelRaptor\Notifications\ExportCompletedNotification;
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

                $export = new $exportClass($query, $this->getExportColumns());

                if ($this->shouldUseJob()) {
                    // Envia para fila e depois notifica o usuário quando concluir
                    Excel::queue($export, $filePath)->chain([
                        function () use ($user, $fileName, $resourceName) {
                            $downloadUrl = route('download.export', ['filename' => $fileName]);
                            $user->notify(new ExportCompletedNotification($fileName, $downloadUrl, $resourceName));
                        }
                    ]);

                    return [
                        'notification' => [
                            'title' => 'Exportação em Fila',
                            'text' => 'Sua exportação foi iniciada. Você será notificado quando estiver pronta.',
                            'type' => 'success',
                        ],
                    ];
                }

                try {
                    Excel::store($export, $filePath);

                    // Envia notificação imediata para exportação síncrona
                    $downloadUrl = route('download.export', ['filename' => $fileName]);
                    $user->notify(new ExportCompletedNotification($fileName, $downloadUrl, $resourceName));

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

    public function getModelClass(): ?string
    {
        if ($this->query) {
            return get_class($this->query->getModel());
        }
        return $this->modelClass;
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
