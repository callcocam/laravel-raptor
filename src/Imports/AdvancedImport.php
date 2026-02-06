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
        $processOrder = $this->getProcessOrder();

        foreach ($processOrder as $sheetIndex) {
            $sheet = $this->sheets[$sheetIndex];
            $sheetName = $sheet->getName();

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
        }

        return $importSheets;
    }

    /**
     * Determina a ordem de processamento das sheets
     * Sheets relacionadas devem ser processadas antes da principal
     */
    protected function getProcessOrder(): array
    {
        $order = [];
        $processed = [];

        foreach ($this->sheets as $index => $sheet) {
            if (in_array($index, $processed)) {
                continue;
            }

            // Se tem sheets relacionadas, processa elas primeiro
            if ($sheet->hasRelatedSheets()) {
                // Adiciona as sheets relacionadas primeiro
                foreach ($sheet->getRelatedSheets() as $relatedSheet) {
                    $relatedIndex = $this->findSheetIndex($relatedSheet->getName());
                    if ($relatedIndex !== null && ! in_array($relatedIndex, $processed)) {
                        $order[] = $relatedIndex;
                        $processed[] = $relatedIndex;
                    }
                }
            }

            // Depois adiciona a sheet principal
            $order[] = $index;
            $processed[] = $index;
        }

        return $order;
    }

    /**
     * Encontra o índice de uma sheet pelo nome
     */
    protected function findSheetIndex(string $sheetName): ?int
    {
        foreach ($this->sheets as $index => $sheet) {
            if ($sheet->getName() === $sheetName) {
                return $index;
            }
        }

        return null;
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
            $service->processRow($row->toArray(), $this->currentRow);
        }

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
        return 500;
    }

    /**
     * Tamanho do chunk para leitura
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
