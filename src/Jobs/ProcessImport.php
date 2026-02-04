<?php

namespace Callcocam\LaravelRaptor\Jobs;

use Callcocam\LaravelRaptor\Imports\DefaultImport;
use Callcocam\LaravelRaptor\Notifications\ImportCompletedNotification;
use Callcocam\LaravelRaptor\Events\ImportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ProcessImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $filePath,
        protected string $modelClass,
        protected ?array $columnMapping,
        protected string $importClass,
        protected string $resourceName,
        protected int|string $userId,
        protected ?string $connectionName = null
    ) {}

    public function handle(): void
    {
        // Cria a instância do import com a conexão correta
        $import = new $this->importClass($this->modelClass, $this->columnMapping, $this->connectionName);

        // Processa a importação
        Excel::import($import, $this->filePath, 'local');

        // Remove o arquivo temporário
        if (file_exists(storage_path('app/' . $this->filePath))) {
            unlink(storage_path('app/' . $this->filePath));
        }

        // Obtém estatísticas da importação (se disponível)
        $totalRows = $import->getRowCount() ?? 0;
        $successfulRows = $import->getSuccessfulCount() ?? $totalRows;
        $failedRows = $import->getFailedCount() ?? 0;

        // Envia notificação ao usuário
        $user = \App\Models\User::find($this->userId);
        if ($user) {
            $user->notify(new ImportCompletedNotification(
                $this->resourceName,
                true // Indica que foi processado via job
            ));
        }

        // Dispara evento de broadcast para atualização em tempo real
        event(new ImportCompleted(
            userId: $this->userId,
            modelName: class_basename($this->modelClass),
            totalRows: $totalRows,
            successfulRows: $successfulRows,
            failedRows: $failedRows,
            fileName: basename($this->filePath)
        ));
    }
}
