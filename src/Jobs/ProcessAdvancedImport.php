<?php

namespace Callcocam\LaravelRaptor\Jobs;

use Callcocam\LaravelRaptor\Events\ImportCompleted;
use Callcocam\LaravelRaptor\Imports\AdvancedImport;
use Callcocam\LaravelRaptor\Notifications\ImportCompletedNotification;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ProcessAdvancedImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $filePath,
        protected array $sheetsData, // Array serializado das sheets
        protected string $resourceName,
        protected int|string $userId,
        protected ?string $connectionName = null,
        protected ?array $connectionConfig = null,
        protected ?string $originalFileName = null
    ) {}

    public function handle(): void
    {
        // Se temos a configuração da conexão, registra ela dinamicamente
        if ($this->connectionName && $this->connectionConfig) {
            config(["database.connections.{$this->connectionName}" => $this->connectionConfig]);
            \DB::purge($this->connectionName);
        }

        // Reconstrói as sheets a partir dos dados serializados
        $sheets = $this->reconstructSheets($this->sheetsData);

        // Cria a instância do import avançado
        $import = new AdvancedImport(
            $sheets,
            $this->connectionName,
            $this->originalFileName
        );

        // Processa a importação
        Excel::import($import, $this->filePath, 'local');

        // Remove o arquivo temporário
        if (file_exists(storage_path('app/'.$this->filePath))) {
            unlink(storage_path('app/'.$this->filePath));
        }

        // Obtém estatísticas da importação
        $totalRows = $import->getTotalRows();
        $successfulRows = $import->getSuccessfulRows();
        $failedRows = $import->getFailedRows();

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
            modelName: $this->resourceName,
            totalRows: $totalRows,
            successfulRows: $successfulRows,
            failedRows: $failedRows,
            fileName: $this->originalFileName ?? basename($this->filePath)
        ));
    }

    /**
     * Reconstrói as sheets a partir dos dados serializados
     */
    protected function reconstructSheets(array $sheetsData): array
    {
        $sheets = [];

        foreach ($sheetsData as $sheetData) {
            $sheet = Sheet::make($sheetData['name']);

            // Reconstrói as propriedades da sheet
            if (isset($sheetData['modelClass'])) {
                $sheet->modelClass($sheetData['modelClass']);
            }

            if (isset($sheetData['tableName'])) {
                $sheet->table(
                    $sheetData['tableName'],
                    $sheetData['database'] ?? null
                );
            }

            if (isset($sheetData['connection'])) {
                $sheet->connection($sheetData['connection']);
            }

            if (isset($sheetData['serviceClass'])) {
                $sheet->serviceClass($sheetData['serviceClass']);
            }

            // Reconstrói as colunas
            if (isset($sheetData['columns'])) {
                $columns = [];

                foreach ($sheetData['columns'] as $columnData) {
                    $columnClass = $columnData['class'] ?? null;

                    if ($columnClass && class_exists($columnClass)) {
                        $column = new $columnClass($columnData['name'], $columnData['label'] ?? null);

                        // Aplica as propriedades da coluna
                        if (isset($columnData['rules'])) {
                            $column->rules($columnData['rules']);
                        }

                        if (isset($columnData['messages'])) {
                            $column->messages($columnData['messages']);
                        }

                        if (isset($columnData['default'])) {
                            $column->defaultValue($columnData['default']);
                        }

                        if (isset($columnData['format'])) {
                            $column->format($columnData['format']);
                        }

                        if (isset($columnData['cast'])) {
                            $column->cast($columnData['cast']);
                        }

                        if (isset($columnData['index'])) {
                            $column->index($columnData['index']);
                        }

                        $columns[] = $column;
                    }
                }

                $sheet->columns($columns);
            }

            // Reconstrói sheets relacionadas
            if (isset($sheetData['relatedSheets']) && is_array($sheetData['relatedSheets'])) {
                foreach ($sheetData['relatedSheets'] as $relatedSheetData) {
                    $relatedSheet = $this->reconstructSingleSheet($relatedSheetData);

                    // Adiciona manualmente como sheet relacionada
                    if ($relatedSheet) {
                        $lookupKey = $relatedSheetData['lookupKey'] ?? 'id';
                        $sheet->addSheet($relatedSheet->getName(), $lookupKey);

                        // Copia as configurações da sheet relacionada
                        $lastRelatedSheet = end($sheet->getRelatedSheets());
                        if ($lastRelatedSheet) {
                            if ($relatedSheet->getModelClass()) {
                                $lastRelatedSheet->modelClass($relatedSheet->getModelClass());
                            }
                            if ($relatedSheet->getTableName()) {
                                $lastRelatedSheet->table($relatedSheet->getTableName(), $relatedSheet->getDatabase());
                            }
                            $lastRelatedSheet->columns($relatedSheet->getColumns());
                        }
                    }
                }
            }

            $sheets[] = $sheet;
        }

        return $sheets;
    }

    /**
     * Reconstrói uma única sheet a partir dos dados serializados
     */
    protected function reconstructSingleSheet(array $sheetData): ?Sheet
    {
        $sheet = Sheet::make($sheetData['name']);

        // Reconstrói as propriedades da sheet
        if (isset($sheetData['modelClass'])) {
            $sheet->modelClass($sheetData['modelClass']);
        }

        if (isset($sheetData['tableName'])) {
            $sheet->table(
                $sheetData['tableName'],
                $sheetData['database'] ?? null
            );
        }

        if (isset($sheetData['connection'])) {
            $sheet->connection($sheetData['connection']);
        }

        if (isset($sheetData['serviceClass'])) {
            $sheet->serviceClass($sheetData['serviceClass']);
        }

        if (isset($sheetData['lookupKey'])) {
            $sheet->lookupKey($sheetData['lookupKey']);
        }

        // Reconstrói as colunas
        if (isset($sheetData['columns'])) {
            $columns = [];

            foreach ($sheetData['columns'] as $columnData) {
                $columnClass = $columnData['class'] ?? null;

                if ($columnClass && class_exists($columnClass)) {
                    $column = new $columnClass($columnData['name'], $columnData['label'] ?? null);

                    // Aplica as propriedades da coluna
                    if (isset($columnData['rules'])) {
                        $column->rules($columnData['rules']);
                    }

                    if (isset($columnData['messages'])) {
                        $column->messages($columnData['messages']);
                    }

                    if (isset($columnData['default'])) {
                        $column->defaultValue($columnData['default']);
                    }

                    if (isset($columnData['format'])) {
                        $column->format($columnData['format']);
                    }

                    if (isset($columnData['cast'])) {
                        $column->cast($columnData['cast']);
                    }

                    if (isset($columnData['index'])) {
                        $column->index($columnData['index']);
                    }

                    $columns[] = $column;
                }
            }

            $sheet->columns($columns);
        }

        return $sheet;
    }
}
