<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Imports;

use Callcocam\LaravelRaptor\Services\DefaultImportService;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AdvancedImport implements WithMultipleSheets
{
    protected int $totalRows = 0;

    protected int $successfulRows = 0;

    protected int $failedRows = 0;

    protected array $errors = [];

    protected array $sheetServices = [];

    /**
     * @param  array<Sheet>  $sheets
     */
    public function __construct(
        protected array $sheets,
        protected ?string $connection = null,
        protected ?string $fileName = null
    ) {}

    /**
     * Define as sheets que serão processadas
     */
    public function sheets(): array
    {
        $importSheets = [];

        $addSheetImport = function (Sheet $sheet) use (&$importSheets) {
            $sheetName = $sheet->getName();

            if (isset($importSheets[$sheetName])) {
                return;
            }

            $importSheets[$sheetName] = new SheetImport(
                $sheet,
                $this->connection,
                function ($successful, $failed, $errors, $service) use ($sheetName) {
                    $this->successfulRows += $successful;
                    $this->failedRows += $failed;
                    $this->errors = array_merge($this->errors, $errors);

                    // Armazena o service para compartilhar dados relacionados
                    $this->sheetServices[$sheetName] = $service;
                },
                $this->sheetServices
            );
        };

        foreach ($this->sheets as $sheet) {
            if ($sheet->hasRelatedSheets()) {
                foreach ($sheet->getRelatedSheets() as $relatedSheet) {
                    $addSheetImport($relatedSheet);
                }
            }

            $addSheetImport($sheet);
        }

        return $importSheets;
    }

    public function getSuccessfulRows(): int
    {
        return $this->successfulRows;
    }

    public function getFailedRows(): int
    {
        return $this->failedRows;
    }

    public function getTotalRows(): int
    {
        return $this->successfulRows + $this->failedRows;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Classe interna para processar cada sheet individualmente
 */
class SheetImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    protected int $currentRow = 1;

    public function __construct(
        protected Sheet $sheet,
        protected ?string $connection = null,
        protected ?\Closure $statsCallback = null,
        protected array &$sheetServices = []
    ) {}

    /**
     * Processa a collection de linhas da sheet
     */
    public function collection(Collection $rows)
    {
        $serviceClass = $this->sheet->getServiceClass() ?? DefaultImportService::class;

        $service = new $serviceClass($this->sheet, $this->connection);

        // Se esta sheet tem uma sheet pai com dados relacionados, passa os dados
        if ($this->sheet->isRelatedSheet()) {
            $parentSheet = $this->sheet->getParentSheet();
            if ($parentSheet && isset($this->sheetServices[$parentSheet->getName()])) {
                $parentService = $this->sheetServices[$parentSheet->getName()];
                // Não precisa fazer nada aqui, os dados relacionados serão coletados
            }
        }

        // Se esta sheet tem sheets relacionadas, passa os dados coletados das relacionadas
        if ($this->sheet->hasRelatedSheets()) {
            $relatedData = [];

            foreach ($this->sheet->getRelatedSheets() as $relatedSheet) {
                $relatedSheetName = $relatedSheet->getName();
                if (isset($this->sheetServices[$relatedSheetName])) {
                    $relatedService = $this->sheetServices[$relatedSheetName];
                    $relatedData = array_merge($relatedData, $relatedService->getRelatedData());
                }
            }

            if (! empty($relatedData)) {
                $service->setRelatedData($relatedData);
            }
        }

        foreach ($rows as $row) {
            $this->currentRow++;

            // Log a cada 100 linhas
            if ($this->currentRow % 100 === 0) {
                \Log::info('[AdvancedImport] Processando', [
                    'sheet' => $this->sheet->getName(),
                    'row' => $this->currentRow,
                    'successful' => $service->getSuccessfulRows(),
                    'failed' => $service->getFailedRows(),
                ]);
            }

            $service->processRow($row->toArray(), $this->currentRow);
        }

        \Log::info('[AdvancedImport] Sheet concluída', [
            'sheet' => $this->sheet->getName(),
            'total_rows' => $this->currentRow - 1,
            'successful' => $service->getSuccessfulRows(),
            'failed' => $service->getFailedRows(),
        ]);

        // Callback para atualizar estatísticas no parent
        if ($this->statsCallback) {
            ($this->statsCallback)(
                $service->getSuccessfulRows(),
                $service->getFailedRows(),
                $service->getErrors(),
                $service
            );
        }
    }

    /**
     * Tamanho do batch para inserção
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Tamanho do chunk para leitura
     */
    public function chunkSize(): int
    {
        return 100;
    }
}
