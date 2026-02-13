<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Jobs;

use Callcocam\LaravelRaptor\Events\ExportCompleted;
use Callcocam\LaravelRaptor\Exports\DefaultExport;
use Callcocam\LaravelRaptor\Notifications\ExportCompletedNotification;
use Callcocam\LaravelRaptor\Traits\NotifiesUserOnCompletion;
use Callcocam\LaravelRaptor\Traits\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

class ProcessExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use NotifiesUserOnCompletion;
    use TenantAwareJob;

    public function __construct(
        protected string $modelClass,
        protected array $filters,
        protected array $columns,
        protected string $fileName,
        protected string $filePath,
        protected string $resourceName,
        protected int|string $userId,
    ) {
        $this->captureTenantContext();
    }

    public function middleware(): array
    {
        return $this->tenantMiddleware();
    }

    public function handle(): void
    {
        $model = app($this->modelClass);
        $query = $model->newQuery();
        // Aplica os filtros processados (já sem page, per_page e com filtros extraídos)
        if (! empty($this->filters)) {
            foreach ($this->filters as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } elseif ($value === 'true' || $value === 'false') {
                    if ($value === 'true') {
                        $query->whereNotNull($column);
                    } else {
                        $query->whereNull($column);
                    }
                } elseif (! empty($value)) {
                    $query->where($column, $value);
                }
            }
        }

        // Cria o export
        $export = new DefaultExport($query, $this->columns);

        // Gera o arquivo
        Excel::store($export, $this->filePath, config('raptor.export.disk', 'public'));

        // Obtém o total de linhas exportadas
        $totalRows = $query->count();

        // Gera a URL de download (evita RouteNotFoundException no queue worker)
        $downloadUrl = $this->resolveDownloadExportUrl($this->fileName);

        $this->notifyUser(new ExportCompletedNotification(
            $this->fileName,
            $downloadUrl,
            $this->resourceName,
            true // Indica que foi processado via job
        ));

        // Dispara evento de broadcast para atualização em tempo real
        event(new ExportCompleted(
            userId: $this->userId,
            modelName: class_basename($this->modelClass),
            totalRows: $totalRows,
            filePath: $this->filePath,
            fileName: $this->fileName
        ));

        Log::info('✅ ProcessExport concluído com sucesso', [
            'fileName' => $this->fileName,
            'totalRows' => $totalRows,
            'tenantId' => $this->tenantId,
            'downloadUrl' => $downloadUrl,
        ]);
    }

    /** Resolve URL de download da exportação sem depender de rota nomeada (funciona no queue worker). */
    protected function resolveDownloadExportUrl(string $filename): string
    {
        foreach (['tenant.download.export', 'landlord.download.export', 'download.export'] as $name) {
            if (Route::has($name)) {
                return route($name, ['filename' => $filename]);
            }
        }

        return url('download-export/'.$filename);
    }
}
