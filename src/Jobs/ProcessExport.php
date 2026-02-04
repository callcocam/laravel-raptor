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
        protected ?string $connectionName = null
    ) {}

    public function handle(): void
    {
        // Reconstrói a query a partir do model class com a conexão correta
        $model = app($this->modelClass);
        if ($this->connectionName) {
            $model->setConnectionName($this->connectionName);
        }
        $query = $model->newQuery();

        // Aplica os filtros
        if (!empty($this->filters)) {
            foreach ($this->filters as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } elseif (!empty($value)) {
                    $query->where($column, 'like', "%{$value}%");
                }
            }
        }

        // Cria o export
        $export = new DefaultExport($query, $this->columns);

        // Gera o arquivo
        Excel::store($export, $this->filePath, 'local');

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
