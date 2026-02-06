<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Columns\Types;

use Callcocam\LaravelRaptor\Support\Import\Columns\Column;

class ImportDate extends Column
{
    public function render(mixed $value, $row = null): mixed
    {
        if (empty($value)) {
            return null;
        }

        // Se já é um DateTime, retorna
        if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return $value;
        }

        // Se é timestamp numérico do Excel (dias desde 1900-01-01)
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
        }

        // Tenta converter string para DateTime
        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
