<?php

namespace Callcocam\LaravelRaptor\Exports;

use Callcocam\LaravelRaptor\Events\ExportCompleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

class DefaultExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    protected Builder $query;
    protected array $columns;
    protected ?string $filePath = null;
    protected ?string $fileName = null;

    public function __construct(Builder $query, array $columns, ?string $filePath = null, ?string $fileName = null)
    {
        $this->query = $query;
        $this->columns = $columns;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function map($row): array
    {
        return array_map(function ($key) use ($row) {
            return data_get($row, $key);
        }, array_keys($this->columns));
    }

    /**
     * Registra eventos do Excel
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->dispatchCompletedEvent($event);
            },
        ];
    }

    /**
     * Dispara o evento de exportação concluída
     */
    protected function dispatchCompletedEvent(AfterSheet $event): void
    {
        if ($userId = Auth::id()) {
            $totalRows = $event->sheet->getHighestDataRow() - 1; // Remove header

            ExportCompleted::dispatch(
                userId: $userId,
                modelName: class_basename($this->query->getModel()),
                totalRows: $totalRows,
                filePath: $this->filePath ?? 'exports/export.xlsx',
                fileName: $this->fileName
            );
        }
    }
}
