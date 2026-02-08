<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Coleta as linhas de uma aba do Excel para um array compartilhado.
 * Usado pelo AdvancedImport: cada aba registrada grava suas linhas no collector.
 */
class SheetRowCollectorImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        protected string $sheetName,
        /** @var array<string, array<int, array{row: int, data: array<string, mixed>}>> */
        protected array &$collector
    ) {}

    public function collection(Collection $rows): void
    {
        $data = [];
        $rowNumber = 1; // cabeçalho na linha 1

        foreach ($rows as $row) {
            $rowNumber++;
            $data[] = [
                'row' => $rowNumber,
                'data' => $row->toArray(),
            ];
        }

        $this->collector[$this->sheetName] = $data;
    }
}
