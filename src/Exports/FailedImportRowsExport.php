<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Export para Excel com as linhas que falharam na importação (dados originais + Linha + Erro).
 *
 * @param  array<int, array{row: int, data: array<string, mixed>, message: string}>  $failedRowsData
 */
class FailedImportRowsExport implements FromArray
{
    public function __construct(
        protected array $failedRowsData
    ) {}

    public function array(): array
    {
        if (empty($this->failedRowsData)) {
            return [];
        }

        $dataKeys = [];
        foreach ($this->failedRowsData as $item) {
            $dataKeys = array_unique(array_merge($dataKeys, array_keys($item['data'])));
        }
        $dataKeys = array_values($dataKeys);
        $headers = array_merge($dataKeys, ['Linha', 'Erro']);
        $rows = [$headers];

        foreach ($this->failedRowsData as $item) {
            $row = [];
            foreach ($dataKeys as $key) {
                $value = $item['data'][$key] ?? '';
                $row[] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
            }
            $row[] = $item['row'];
            $row[] = $item['message'];
            $rows[] = $row;
        }

        return $rows;
    }
}
