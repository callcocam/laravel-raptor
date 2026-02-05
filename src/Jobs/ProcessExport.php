<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Jobs;

use Callcocam\LaravelRaptor\Exports\DefaultExport;
use Callcocam\LaravelRaptor\Notifications\ExportCompletedNotification;
use Callcocam\LaravelRaptor\Events\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProcessExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $modelClass,
        protected array $filters,
        protected array $columns,
        protected string $fileName,
        protected string $filePath,
        protected string $resourceName,
        protected int|string $userId,
        protected ?string $connectionName = null,
        protected ?array $connectionConfig = null
    ) {}

    public function handle(): void
    {
        // Se temos a configuração da conexão, registra ela dinamicamente
        if ($this->connectionName && $this->connectionConfig) {
            config(["database.connections.{$this->connectionName}" => $this->connectionConfig]);
            \DB::purge($this->connectionName);
        }
Log::info("Verificar se pegar dados da config: ",[
    'clientID' => config("app.current_client_id"),
    'tenantID' => config("app.current_tenant_id"),
]);
        // Reconstrói a query a partir do model class com a conexão correta
        $model = app($this->modelClass);
        if ($this->connectionName) {
            $model->setConnection($this->connectionName);
        }
        $query = $model->newQuery(); 
        // Aplica os filtros processados (já sem page, per_page e com filtros extraídos)
        if (!empty($this->filters)) {
            foreach ($this->filters as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } elseif ($value === "true" || $value === "false") {
                    if ($value === "true") {
                        $query->whereNotNull($column);
                    } else {
                        $query->whereNull($column);
                    }
                } elseif (!empty($value)) {
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

        // Gera a URL de download
        $downloadUrl = route('download.export', ['filename' => $this->fileName]);

        // Envia notificação ao usuário
        $user = \App\Models\User::find($this->userId);
        if ($user) {
            $user->notify(new ExportCompletedNotification(
                $this->fileName,
                $downloadUrl,
                $this->resourceName,
                true // Indica que foi processado via job
            ));
        }

        // Dispara evento de broadcast para atualização em tempo real
        event(new ExportCompleted(
            userId: $this->userId,
            modelName: class_basename($this->modelClass),
            totalRows: $totalRows,
            filePath: $this->filePath,
            fileName: $this->fileName
        ));
    }
}
