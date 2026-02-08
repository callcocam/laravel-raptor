<?php

namespace Callcocam\LaravelRaptor\Services;

use App\Models\User;
use Callcocam\LaravelRaptor\Events\ImportCompleted;
use Callcocam\LaravelRaptor\Imports\AdvancedImport;
use Callcocam\LaravelRaptor\Jobs\ProcessAdvancedImport;
use Callcocam\LaravelRaptor\Notifications\ImportCompletedNotification;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AdvancedImportDispatcher
{
    /**
     * @param  array<Sheet>  $sheets
     */
    public function dispatch(
        string $fileName,
        string $resourceName,
        int|string $userId,
        ?string $modelClass = null,
        array $sheets = [],
        ?string $originalFileName = null
    ): array {
        $sheetsData = $this->serializeSheets($sheets);
        $connection = null;
        $connectionConfig = null;

        if ($modelClass) {
            $model = app($modelClass);
            $connection = $model->getConnectionName();
            $connectionConfig = config("database.connections.{$connection}");
        } elseif (! empty($sheets)) {
            $firstSheet = $sheets[0] ?? null;
            if ($firstSheet instanceof Sheet) {
                $connection = $firstSheet->getConnection();
                if ($connection) {
                    $connectionConfig = config("database.connections.{$connection}");
                }
            }
        }

        ProcessAdvancedImport::dispatch(
            $fileName,
            $sheetsData,
            $resourceName,
            $userId,
            $connection,
            $connectionConfig,
            $originalFileName
        );

        return [
            'notification' => [
                'title' => 'Importação Iniciada',
                'text' => 'Sua importação está sendo processada em múltiplas planilhas. Você receberá uma notificação quando estiver concluída.',
                'type' => 'info',
            ],
        ];
    }

    /**
     * Processa importação avançada com múltiplas sheets
     *
     * @param  array<Sheet>  $sheets
     */
    public function processAdvancedImport(
        string $fileName,
        string $originalFileName,
        User $user,
        ?string $modelClass = null,
        array $sheets = []
    ): array {
        $connection = null;

        if ($modelClass) {
            $connection = app($modelClass)->getConnectionName();
        } elseif (! empty($sheets)) {
            $firstSheet = $sheets[0] ?? null;
            if ($firstSheet instanceof Sheet) {
                $connection = $firstSheet->getConnection();
            }
        }

        $import = new AdvancedImport(
            $sheets,
            $connection,
            $originalFileName
        );

        Excel::import($import, $fileName, 'local');

        $totalRows = $import->getTotalRows();
        $successfulRows = $import->getSuccessfulRows();
        $failedRows = $import->getFailedRows();

        if (file_exists(storage_path('app/' . $fileName))) {
            unlink(storage_path('app/' . $fileName));
        }

        $resourceName = $this->getResourceNameFromSheets($modelClass, $sheets);
        $user->notify(new ImportCompletedNotification($resourceName));

        event(new ImportCompleted(
            userId: $user->id,
            modelName: $resourceName,
            totalRows: $totalRows,
            successfulRows: $successfulRows,
            failedRows: $failedRows,
            fileName: $originalFileName
        ));

        return [
            'notification' => [
                'title' => 'Importação Concluída',
                'text' => "Importados: {$successfulRows} | Erros: {$failedRows}. Verifique suas notificações.",
                'type' => $failedRows > 0 ? 'warning' : 'success',
            ],
        ];
    }

    /**
     * @param  array<Sheet>  $sheets
     */
    public function getResourceNameFromSheets(?string $modelClass = null, array $sheets = []): string
    {
        if ($modelClass) {
            $modelName = class_basename($modelClass);

            return Str::plural(Str::lower(str_replace('_', ' ', Str::snake($modelName))));
        }

        if (! empty($sheets)) {
            $firstSheet = $sheets[0] ?? null;

            if ($firstSheet instanceof Sheet) {
                if ($sheetModelClass = $firstSheet->getModelClass()) {
                    return Str::plural(Str::lower(str_replace('_', ' ', Str::snake(class_basename($sheetModelClass)))));
                }

                if ($tableName = $firstSheet->getTableName()) {
                    return Str::plural(Str::lower(str_replace('_', ' ', $tableName)));
                }
            }
        }

        return 'registros';
    }

    /**
     * @param  array<Sheet>  $sheets
     */
    public function serializeSheets(array $sheets): array
    {
        $serialized = [];

        foreach ($sheets as $sheet) {
            if (! $sheet instanceof Sheet) {
                continue;
            }

            $sheetData = [
                'name' => $sheet->getName(),
                'modelClass' => $sheet->getModelClass(),
                'tableName' => $sheet->getTableName(),
                'database' => $sheet->getDatabase(),
                'connection' => $sheet->getConnection(),
                'serviceClass' => $sheet->getServiceClass(),
                'generateId' => $sheet->shouldGenerateId(),
                'generateIdClass' => $sheet->getGenerateIdClass(),
                'columns' => [],
                'relatedSheets' => [],
            ];

            foreach ($sheet->getColumns() as $column) {
                $sheetData['columns'][] = [
                    'class' => get_class($column),
                    'name' => $column->getName(),
                    'label' => $column->getLabel(),
                    'index' => $column->getIndex(),
                    'rules' => $column->getRules(),
                    'messages' => $column->getMessages(),
                    'default' => $column->getDefaultValue(),
                    'format' => $column->getFormat(),
                    'cast' => $column->getCast(),
                    'hidden' => $column->isHidden(),
                    'sheet' => $column->getSheetName(),
                ];
            }

            if ($sheet->hasRelatedSheets()) {
                foreach ($sheet->getRelatedSheets() as $relatedSheet) {

                    $relatedSheetData = [
                        'name' => $relatedSheet->getName(),
                        'modelClass' => $relatedSheet->getModelClass(),
                        'tableName' => $relatedSheet->getTableName(),
                        'database' => $relatedSheet->getDatabase(),
                        'connection' => $relatedSheet->getConnection(),
                        'serviceClass' => $relatedSheet->getServiceClass(),
                        'lookupKey' => $relatedSheet->getLookupKey(),
                        'generateId' => $relatedSheet->shouldGenerateId(),
                        'generateIdClass' => $relatedSheet->getGenerateIdClass(),
                        'columns' => [],
                    ];
                    foreach ($relatedSheet->getColumns() as $column) {
                        dd($column);
                        $relatedSheetData['columns'][] = [
                            'class' => get_class($column),
                            'name' => $column->getName(),
                            'label' => $column->getLabel(),
                            'index' => $column->getIndex(),
                            'rules' => $column->getRules(),
                            'messages' => $column->getMessages(),
                            'default' => $column->getDefaultValue(),
                            'format' => $column->getFormat(),
                            'cast' => $column->getCast(),
                            'hidden' => $column->isHidden(),
                            'sheet' => $column->getSheetName(),
                        ];
                    }

                    $sheetData['relatedSheets'][] = $relatedSheetData;
                }
            }

            $serialized[] = $sheetData;
        }

        return $serialized;
    }
}
