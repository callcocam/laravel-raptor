<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Imports;

use Callcocam\LaravelRaptor\Services\DefaultImportService;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
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
        $sheetNames = $this->collectSheetNames();

        foreach ($sheetNames as $name) {
            $imports[$name] = new SheetRowCollectorImport($name, $this->collector);
        }

        return $imports;
    }

    /**
     * Aba configurada mas ausente no arquivo: não falha (comportamento desejado).
     *
     * @param  int|string  $sheetName  Índice ou nome da aba
     */
    public function onUnknownSheet($sheetName): void
    {
    }

    /**
     * Executa após Excel::import(): mescla principal + relatedSheets e chama o service por linha.
     */
    public function process(): void
    {
        foreach ($this->sheets as $sheet) {
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
     * Nomes únicos de abas: principal + related de cada sheet.
     *
     * @return array<int, string>
     */
    protected function collectSheetNames(): array
    {
        $names = [];

        foreach ($this->sheets as $sheet) {
            $names[$sheet->getName()] = true;
            foreach ($sheet->getRelatedSheets() as $related) {
                $names[$related->getName()] = true;
            }
        }

        return array_keys($names);
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
}
