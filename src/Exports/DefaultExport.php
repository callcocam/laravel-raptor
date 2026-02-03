<?php

namespace Callcocam\LaravelRaptor\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class DefaultExport implements FromQuery, WithHeadings, WithMapping
{
    protected Builder $query;
    protected array $columns;

    public function __construct(Builder $query, array $columns)
    {
        $this->query = $query;
        $this->columns = $columns;
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
}
