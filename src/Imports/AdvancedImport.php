<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Imports;

use Callcocam\LaravelRaptor\Services\DefaultImportService;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Support\Import\Contracts\AfterProcessHookInterface;
use Callcocam\LaravelRaptor\Support\Import\Contracts\ImportServiceInterface;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Leitor Excel com maatwebsite/excel: múltiplas abas, sheet principal + relatedSheets.
 *
 * Cada Sheet = uma tabela; relatedSheets = abas com colunas da mesma tabela (lookupKey).
 * 1) Coleta linhas de cada aba (principal e relacionadas) que existir no arquivo.
 * 2) Após Excel::import(), process(): para cada sheet principal, mescla com relatedSheets
 *    presentes (por lookupKey); relatedSheets ausentes são ignoradas.
 */
class AdvancedImport implements SkipsUnknownSheets, WithMultipleSheets
{
    /**
     * Linhas coletadas por nome da aba: [ sheetName => [ ['row' => 2, 'data' => [...]], ... ] ]
     *
     * @var array<string, array<int, array{row: int, data: array<string, mixed>}>>
     */
    protected array $collector = [];

    protected int $totalRows = 0;

    protected int $successfulRows = 0;

    protected int $failedRows = 0;

    /** @var array<int, array{row: int, message: string, column?: string}> */
    protected array $errors = [];

    /** @var array<int, array{row: int, data: array<string, mixed>, message: string}> */
    protected array $failedRowsData = [];

    /** Nomes das sheets principais processadas em chunk (para não reprocessar em process()). */
    protected array $chunkedSheetNames = [];

    /**
     * @param  array<int, Sheet>  $sheets  Apenas sheets principais (cada uma pode ter relatedSheets)
     */
    public function __construct(
        protected array $sheets,
        protected ?string $connection = null,
        protected ?array $context = null
    ) {}

    public function sheets(): array
    {
        $imports = [];
        $ordered = $this->collectSheetNamesOrdered();
        $mainSheetsByName = [];
        foreach ($this->sheets as $sheet) {
            $mainSheetsByName[$sheet->getName()] = $sheet;
        }

        $chunkedMainSheetAdded = false;
        foreach ($ordered as $name) {
            $mainSheet = $mainSheetsByName[$name] ?? null;
            if ($mainSheet !== null && $mainSheet->getChunkSize() > 0 && ! $chunkedMainSheetAdded) {
                $this->chunkedSheetNames[] = $name;
                $chunkedMainSheetAdded = true;
                // Índice 0 = primeira aba do workbook (Maatwebsite traduz para o nome real).
                // Assim a importação funciona mesmo se o nome da aba for diferente (ex.: "Produtos" vs "Tabela de produtos").
                $imports[0] = new ChunkedSheetProcessorImport(
                    $this,
                    $mainSheet,
                    $this->collector,
                    $this->connection,
                    $this->context
                );
            } elseif ($mainSheet !== null && $mainSheet->getChunkSize() > 0) {
                $this->chunkedSheetNames[] = $name;
                $imports[$name] = new ChunkedSheetProcessorImport(
                    $this,
                    $mainSheet,
                    $this->collector,
                    $this->connection,
                    $this->context
                );
            } else {
                $imports[$name] = new SheetRowCollectorImport($name, $this->collector);
            }
        }

        return $imports;
    }

    /** Services das sheets processadas em chunk (para merge em process()). */
    protected array $chunkedServices = [];

    /**
     * Chamado pelo ChunkedSheetProcessorImport após cada linha processada.
     */
    public function addProcessedRowCount(int $n): void
    {
        $this->totalRows += $n;
    }

    /**
     * Chamado pelo ChunkedSheetProcessorImport (registra o service para merge em process()).
     */
    public function registerChunkedService(string $sheetName, ImportServiceInterface $service): void
    {
        $this->chunkedServices[$sheetName] = $service;
    }

    /**
     * Acumula sucesso/falha/erros de um service (usado em process() e para sheets em chunk).
     */
    public function mergeSheetResults(ImportServiceInterface $service): void
    {
        $this->successfulRows += $service->getSuccessfulRows();
        $this->failedRows += $service->getFailedRows();
        $this->errors = array_merge($this->errors, $service->getErrors());
    }

    /**
     * Exposto para ChunkedSheetProcessorImport (mesma lógica de buildRelatedIndexes).
     *
     * @return array<string, array{key: string, byValue: array<string, array<string, mixed>>}>
     */
    public function buildRelatedIndexesForSheet(Sheet $sheet): array
    {
        return $this->buildRelatedIndexes($sheet);
    }

    /**
     * Exposto para ChunkedSheetProcessorImport.
     */
    public function getLookupValueForRow(array $row, string $lookupKey): mixed
    {
        return $this->getLookupValue($row, $lookupKey);
    }

    /**
     * Aba configurada mas ausente no arquivo: não falha (comportamento desejado).
     *
     * @param  int|string  $sheetName  Índice ou nome da aba
     */
    public function onUnknownSheet($sheetName): void {}

    /**
     * Executa após Excel::import(): mescla principal + relatedSheets e chama o service por linha.
     * Sheets processadas em chunk já atualizaram totais durante a leitura; aqui só fazemos merge dos resultados.
     */
    public function process(): void
    {
        foreach ($this->chunkedSheetNames as $name) {
            if (isset($this->chunkedServices[$name])) {
                $this->mergeSheetResults($this->chunkedServices[$name]);
                $this->failedRowsData = array_merge(
                    $this->failedRowsData,
                    $this->chunkedServices[$name]->getFailedRowsData()
                );
                $this->dispatchAfterProcessHook($name, $this->chunkedServices[$name]->getCompletedRows());
            }
        }

        foreach ($this->sheets as $sheet) {
            if (in_array($sheet->getName(), $this->chunkedSheetNames, true)) {
                continue;
            }
            $this->processSheet($sheet);
        }
    }

    protected function processSheet(Sheet $sheet): void
    {
        $mainName = $sheet->getName();
        $mainRows = $this->collector[$mainName] ?? [];

        if (empty($mainRows)) {
            return;
        }

        $serviceClass = $sheet->getServiceClass() ?? DefaultImportService::class;
        $service = new $serviceClass($sheet, $this->connection, $this->context);
        if ($this->context) {
            $service->setContext($this->context);
        }

        $relatedIndexes = $this->buildRelatedIndexes($sheet);

        foreach ($mainRows as $item) {
            $rowNumber = $item['row'];
            $data = $item['data'];

            foreach ($relatedIndexes as $relatedData) {
                $lookupKey = $relatedData['key'];
                $byValue = $relatedData['byValue'];
                $lookupValue = $this->getLookupValue($data, $lookupKey);
                if ($lookupValue !== null && isset($byValue[(string) $lookupValue])) {
                    $data = array_merge($data, $byValue[(string) $lookupValue]);
                }
            }

            $service->processRow($data, $rowNumber);
        }

        $this->totalRows += count($mainRows);
        $this->successfulRows += $service->getSuccessfulRows();
        $this->failedRows += $service->getFailedRows();
        $this->errors = array_merge($this->errors, $service->getErrors());
        $this->failedRowsData = array_merge($this->failedRowsData, $service->getFailedRowsData());

        $this->dispatchAfterProcessHook($mainName, $service->getCompletedRows());
    }

    /**
     * Dispara o hook afterProcess quando a Sheet define afterProcessClass.
     *
     * @param  array<int, array{row: int, data: array<string, mixed>}>  $completedRows
     */
    protected function dispatchAfterProcessHook(string $sheetName, array $completedRows): void
    {
        $sheet = $this->getSheetByName($sheetName);
        if ($sheet === null) {
            return;
        }
        $afterClass = $sheet->getAfterProcessClass();
        if ($afterClass === null || ! class_exists($afterClass)) {
            return;
        }
        $hook = app($afterClass);
        if ($hook instanceof AfterProcessHookInterface) {
            $hook->afterProcess($sheetName, $completedRows);
        }
    }

    protected function getSheetByName(string $name): ?Sheet
    {
        foreach ($this->sheets as $sheet) {
            if ($sheet->getName() === $name) {
                return $sheet;
            }
        }

        return null;
    }

    /**
     * Por relatedSheet que tem dados: [ sheetName => [ 'key' => lookupKey, 'byValue' => [ valor => linha ] ] ].
     *
     * @return array<string, array{key: string, byValue: array<string, array<string, mixed>>}>
     */
    protected function buildRelatedIndexes(Sheet $sheet): array
    {
        $indexes = [];

        foreach ($sheet->getRelatedSheets() as $relatedSheet) {
            $name = $relatedSheet->getName();
            $rows = $this->collector[$name] ?? null;

            if ($rows === null || $rows === []) {
                continue;
            }

            $lookupKey = $relatedSheet->getLookupKey() ?? 'id';
            $index = [];

            foreach ($rows as $item) {
                $data = $item['data'];
                $keyValue = $this->getLookupValue($data, $lookupKey);
                if ($keyValue !== null && $keyValue !== '') {
                    $index[(string) $keyValue] = $data;
                }
            }

            $indexes[$name] = ['key' => $lookupKey, 'byValue' => $index];
        }

        return $indexes;
    }

    protected function getLookupValue(array $row, string $lookupKey): mixed
    {
        $normalized = $this->normalizeHeader($lookupKey);

        return $row[$normalized] ?? $row[$lookupKey] ?? null;
    }

    /**
     * Mesmo padrão do Laravel Excel: lowercase, acentos removidos, espaços → underscore.
     */
    protected function normalizeHeader(string $label): string
    {
        $label = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
        $label = mb_strtolower($label, 'UTF-8');
        $label = preg_replace('/[^a-z0-9]+/', '_', $label);

        return trim($label, '_');
    }

    /**
     * Nomes únicos de abas: related primeiro (para chunk da principal ter dados), depois principais.
     *
     * @return array<int, string>
     */
    protected function collectSheetNamesOrdered(): array
    {
        $related = [];
        $main = [];

        foreach ($this->sheets as $sheet) {
            foreach ($sheet->getRelatedSheets() as $relatedSheet) {
                $related[$relatedSheet->getName()] = true;
            }
            $main[$sheet->getName()] = true;
        }

        return array_merge(array_keys($related), array_keys($main));
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function getSuccessfulRows(): int
    {
        return $this->successfulRows;
    }

    public function getFailedRows(): int
    {
        return $this->failedRows;
    }

    /**
     * @return array<int, array{row: int, message: string, column?: string}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Linhas que falharam com dados originais (para gerar Excel de erros).
     *
     * @return array<int, array{row: int, data: array<string, mixed>, message: string}>
     */
    public function getFailedRowsData(): array
    {
        return $this->failedRowsData;
    }
}
