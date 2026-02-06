<?php

namespace Callcocam\LaravelRaptor\Jobs;

use Callcocam\LaravelRaptor\Services\DefaultImportService;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(
        protected array $rows,
        protected array $sheetData,
        protected int $startRow,
        protected ?string $connectionName = null
    ) {}

    public function handle(): array
    {
        // Reconstrói a sheet a partir dos dados serializados
        $sheet = $this->reconstructSheet($this->sheetData);

        // Cria o service
        $serviceClass = $sheet->getServiceClass() ?? DefaultImportService::class;
        $service = new $serviceClass($sheet, $this->connectionName);

        // Processa as linhas deste chunk
        $rowNumber = $this->startRow;
        foreach ($this->rows as $row) {
            $service->processRow($row, $rowNumber);
            $rowNumber++;
        }

        // Retorna as estatísticas
        return [
            'successful' => $service->getSuccessfulRows(),
            'failed' => $service->getFailedRows(),
            'errors' => $service->getErrors(),
        ];
    }

    protected function reconstructSheet(array $sheetData): Sheet
    {
        $sheet = Sheet::make($sheetData['name']);

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

                    if (isset($columnData['hidden'])) {
                        $column->hidden((bool) $columnData['hidden']);
                    }

                    if (isset($columnData['sheet'])) {
                        $column->sheet($columnData['sheet']);
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
