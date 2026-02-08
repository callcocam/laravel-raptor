<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Imports;

use Callcocam\LaravelRaptor\Services\DefaultImportService;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Support\Import\Contracts\ImportServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Processa a sheet principal em chunks (reduz memória em arquivos grandes).
 * Related sheets já devem ter sido lidas antes (ordem das abas: related primeiro, principal por último).
 */
class ChunkedSheetProcessorImport implements ToModel, WithChunkReading, WithHeadingRow
{
    use RemembersRowNumber;

    /** @var array<string, array{key: string, byValue: array<string, array<string, mixed>>}>|null */
    protected ?array $relatedIndexes = null;

    protected ?ImportServiceInterface $service = null;

    public function __construct(
        protected AdvancedImport $parent,
        protected Sheet $sheet,
        /** @var array<string, array<int, array{row: int, data: array<string, mixed>}>> */
        protected array &$collector,
        protected ?string $connection = null,
        protected ?array $context = null
    ) {}

    public function model(array $row): Model|null
    {
        $rowNumber = $this->getRowNumber() ?? 2;

        if ($this->relatedIndexes === null) {
            $this->relatedIndexes = $this->parent->buildRelatedIndexesForSheet($this->sheet);
        }
        if ($this->service === null) {
            $serviceClass = $this->sheet->getServiceClass() ?? DefaultImportService::class;
            $this->service = new $serviceClass($this->sheet, $this->connection, $this->context);
            if ($this->context) {
                $this->service->setContext($this->context);
            }
        }

        $data = $row;
        foreach ($this->relatedIndexes as $relatedData) {
            $lookupKey = $relatedData['key'];
            $byValue = $relatedData['byValue'];
            $lookupValue = $this->parent->getLookupValueForRow($data, $lookupKey);
            if ($lookupValue !== null && isset($byValue[(string) $lookupValue])) {
                $data = array_merge($data, $byValue[(string) $lookupValue]);
            }
        }

        $this->service->processRow($data, $rowNumber);
        $this->parent->addProcessedRowCount(1);
        // Registra o service uma vez (para merge em process()); sobrescrever com a mesma ref é idempotente.
        $this->parent->registerChunkedService($this->sheet->getName(), $this->service);

        return null;
    }

    public function chunkSize(): int
    {
        return max(500, $this->sheet->getChunkSize());
    }
}
