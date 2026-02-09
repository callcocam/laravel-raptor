<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Jobs;

use Callcocam\LaravelRaptor\Events\ImportCompleted;
use Callcocam\LaravelRaptor\Exports\FailedImportRowsExport;
use Callcocam\LaravelRaptor\Imports\AdvancedImport;
use Callcocam\LaravelRaptor\Notifications\ImportCompletedNotification;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Traits\NotifiesUserOnCompletion;
use Callcocam\LaravelRaptor\Traits\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Job de importação avançada (Excel com múltiplas sheets).
 *
 * Cada sheet pertence a uma tabela; relatedSheets são abas com colunas da mesma tabela.
 * Recebe o caminho do arquivo e o payload das sheets; reconstrói as Sheet e delega
 * ao leitor Excel + ImportService (ler principal, completar com relatedSheets presentes, ignorar ausentes).
 */
class ProcessAdvancedImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use NotifiesUserOnCompletion;
    use TenantAwareJob;

    public int $timeout = 600;

    public int $tries = 3;

    public function __construct(
        /** Caminho relativo ao disco local, ex.: "imports/uuid.xlsx" */
        protected string $filePath,
        /** Payload serializado das sheets (Sheet::toArray()) — apenas sheets principais */
        protected array $sheetsData,
        protected string $resourceName,
        protected int|string $userId,
        protected ?string $originalFileName = null,
        /** Contexto para colunas hidden (tenant_id, user_id) — resolvido no dispatch */
        protected ?array $context = null
    ) {
        $this->captureTenantContext();
    }

    public function handle(): void
    {
        $this->restoreTenantContext();

        $sheets = $this->reconstructSheets($this->sheetsData);

        // Usar o mesmo disco em que a Action salvou (ex.: local = storage/app/private)
        $disk = Storage::disk('local');
        if (! $disk->exists($this->filePath)) {
            return;
        }

        $fullPath = $disk->path($this->filePath);

        $import = new AdvancedImport($sheets, null, $this->context ?? []);
        Excel::import($import, $this->filePath, 'local');
        $import->process();

        $totalRows = $import->getTotalRows();
        $successfulRows = $import->getSuccessfulRows();
        $failedRows = $import->getFailedRows();
        $errors = $import->getErrors();

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $failedReportPath = null;
        $failedRowsData = $import->getFailedRowsData();
        if ($failedRows > 0 && ! empty($failedRowsData)) {
            $failedReportPath = 'imports/failed-'.Str::uuid()->toString().'.xlsx';
            $export = new FailedImportRowsExport($failedRowsData);
            Excel::store($export, $failedReportPath, 'local');
        }

        $this->notifyUser(new ImportCompletedNotification(
            $this->resourceName,
            true,
            null,
            null,
            null,
            null,
            $failedReportPath
        ));

        event(new ImportCompleted(
            userId: $this->userId,
            modelName: $this->resourceName,
            totalRows: $totalRows,
            successfulRows: $successfulRows,
            failedRows: $failedRows,
            fileName: $this->originalFileName ?? basename($this->filePath),
            tenantId: null,
            tenantName: null,
            clientId: null,
            clientName: null,
            failedReportPath: $failedReportPath
        ));
    }

    /**
     * Reconstrói instâncias de Sheet a partir do payload (para uso no worker).
     *
     * @param  array<int, array<string, mixed>>  $sheetsData
     * @return array<int, Sheet>
     */
    protected function reconstructSheets(array $sheetsData): array
    {
        $sheets = [];

        foreach ($sheetsData as $sheetData) {
            $sheet = Sheet::make($sheetData['name']);

            if (! empty($sheetData['modelClass'])) {
                $sheet->modelClass($sheetData['modelClass']);
            }
            if (! empty($sheetData['tableName'])) {
                $sheet->table(
                    $sheetData['tableName'],
                    $sheetData['database'] ?? null
                );
            }
            if (! empty($sheetData['connection'])) {
                $sheet->connection($sheetData['connection']);
            }
            if (! empty($sheetData['serviceClass'])) {
                $sheet->serviceClass($sheetData['serviceClass']);
            }
            if (! empty($sheetData['generateId'])) {
                if (! empty($sheetData['generateIdClass'])) {
                    $sheet->generateIdUsing($sheetData['generateIdClass']);
                } else {
                    $sheet->generateId();
                }
            }

            if (! empty($sheetData['columns'])) {
                $columns = [];
                foreach ($sheetData['columns'] as $columnData) {
                    $columnClass = $columnData['class'] ?? null;
                    if ($columnClass && class_exists($columnClass)) {
                        $column = new $columnClass($columnData['name'], $columnData['label'] ?? null);
                        if (! empty($columnData['rules'])) {
                            $column->rules($columnData['rules']);
                        }
                        if (isset($columnData['hidden'])) {
                            $column->hidden((bool) $columnData['hidden']);
                        }
                        if (! empty($columnData['sheet'])) {
                            $column->sheet($columnData['sheet']);
                        }
                        if (array_key_exists('default', $columnData) && $columnData['default'] !== null && ! $columnData['default'] instanceof \Closure) {
                            $column->defaultValue($columnData['default']);
                        }
                        if (! empty($columnData['format'])) {
                            $column->format($columnData['format']);
                        }
                        if (! empty($columnData['cast'])) {
                            $column->cast($columnData['cast']);
                        }
                        if (isset($columnData['index'])) {
                            $column->index($columnData['index']);
                        }
                        if (! empty($columnData['exclude_from_save']) && method_exists($column, 'excludeFromSave')) {
                            $column->excludeFromSave(true);
                        }
                        if (isset($columnData['depends_on']) && $columnData['depends_on'] !== null && method_exists($column, 'dependsOn')) {
                            $column->dependsOn($columnData['depends_on']);
                        }
                        $columns[] = $column;
                    }
                }
                $sheet->columns($columns);
            }

            if (! empty($sheetData['relatedSheets']) && is_array($sheetData['relatedSheets'])) {
                foreach ($sheetData['relatedSheets'] as $relatedSheetData) {
                    $lookupKey = $relatedSheetData['lookupKey'] ?? 'id';
                    $sheet->addSheet($relatedSheetData['name'], $lookupKey);
                }
            }
            if (! empty($sheetData['chunkSize'])) {
                $sheet->chunkSize((int) $sheetData['chunkSize']);
            }
            if (! empty($sheetData['updateByKeys']) && is_array($sheetData['updateByKeys'])) {
                $sheet->updateBy($sheetData['updateByKeys']);
            }
            if (! empty($sheetData['hierarchicalColumns']) && is_array($sheetData['hierarchicalColumns'])) {
                $sheet->hierarchicalColumns($sheetData['hierarchicalColumns']);
            }
            if (! empty($sheetData['parentColumnName'])) {
                $sheet->parentColumnName($sheetData['parentColumnName']);
            }
            if (! empty($sheetData['hierarchicalValueColumn'])) {
                $sheet->hierarchicalValueColumn($sheetData['hierarchicalValueColumn']);
            }
            if (! empty($sheetData['beforePersistClass'])) {
                $sheet->beforePersistClass($sheetData['beforePersistClass']);
            }
            if (! empty($sheetData['afterPersistClass'])) {
                $sheet->afterPersistClass($sheetData['afterPersistClass']);
            }
            if (! empty($sheetData['afterProcessClass'])) {
                $sheet->afterProcessClass($sheetData['afterProcessClass']);
            }

            $sheets[] = $sheet;
        }

        return $sheets;
    }
}
