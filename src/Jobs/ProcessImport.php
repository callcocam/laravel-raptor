<?php

namespace Callcocam\LaravelRaptor\Jobs;

use Callcocam\LaravelRaptor\Imports\DefaultImport;
use Callcocam\LaravelRaptor\Notifications\ImportCompletedNotification;
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
        protected int|string $userId
    ) {}

    public function handle(): void
    {
        // Cria a instância do import
        $import = new $this->importClass($this->modelClass, $this->columnMapping);

        // Processa a importação
        Excel::import($import, $this->filePath, 'local');

        // Remove o arquivo temporário
        if (file_exists(storage_path('app/' . $this->filePath))) {
            unlink(storage_path('app/' . $this->filePath));
        }

        // Envia notificação ao usuário
        $user = \App\Models\User::find($this->userId);
        if ($user) {
            $user->notify(new ImportCompletedNotification(
                $this->resourceName,
                true // Indica que foi processado via job
            ));
        }
    }
}
