<?php

namespace Callcocam\LaravelRaptor\Services;

use App\Models\User;
use Callcocam\LaravelRaptor\Events\ImportCompleted;
use Callcocam\LaravelRaptor\Notifications\ImportCompletedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SimpleImportProcessor
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function process(
        string $fileName,
        string $originalFileName,
        string $modelClass,
        ?array $columnMapping,
        string $importClass,
        User $user
    ): array {
        $connection = app($modelClass)->getConnectionName();
        $import = new $importClass($modelClass, $columnMapping, $connection);

        Excel::import($import, $fileName, 'local');

        $totalRows = method_exists($import, 'getRowCount') ? $import->getRowCount() : 0;
        $successfulRows = method_exists($import, 'getSuccessfulCount')
            ? $import->getSuccessfulCount()
            : $totalRows;
        $failedRows = method_exists($import, 'getFailedCount') ? $import->getFailedCount() : 0;

        if (file_exists(storage_path('app/'.$fileName))) {
            unlink(storage_path('app/'.$fileName));
        }

        $resourceName = Str::plural(Str::lower(str_replace('_', ' ', Str::snake(class_basename($modelClass)))));
        $user->notify(new ImportCompletedNotification($resourceName));

        event(new ImportCompleted(
            userId: $user->id,
            modelName: class_basename($modelClass),
            totalRows: $totalRows,
            successfulRows: $successfulRows,
            failedRows: $failedRows,
            fileName: $originalFileName
        ));

        return [
            'notification' => [
                'title' => 'Importação Concluída',
                'text' => 'Os registros foram importados com sucesso. Verifique suas notificações.',
                'type' => 'success',
            ],
        ];
    }
}
