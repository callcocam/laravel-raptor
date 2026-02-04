<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Jobs;

use Callcocam\LaravelRaptor\Exports\DefaultExport;
use Callcocam\LaravelRaptor\Notifications\ExportCompletedNotification;
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
        protected int|string $userId
    ) {}

    public function handle(): void
    {
        // Reconstrói a query a partir do model class
        $query = app($this->modelClass)->newQuery();

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
    }
}
