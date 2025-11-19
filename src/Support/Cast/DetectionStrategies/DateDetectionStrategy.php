<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

use Callcocam\LaravelRaptor\Support\Cast\Formatters\DateFormatter;
use Carbon\Carbon;

/**
 * DateDetectionStrategy - Detecta valores de data/hora
 */
class DateDetectionStrategy extends AbstractDetectionStrategy
{
    protected int $priority = 70;

    public function matches(mixed $value): bool
    {
        // DateTime instances
        if ($value instanceof \DateTimeInterface || $value instanceof Carbon) {
            return true;
        }

        // String que parece data
        if (is_string($value) && ! empty($value)) {
            return $this->isDateString($value);
        }

        // Timestamp
        if (is_numeric($value)) {
            return $this->looksLikeTimestamp((float) $value);
        }

        return false;
    }

    public function getFormatter(mixed $value): ?object
    {
        return DateFormatter::relative();
    }

    /**
     * Verifica se string é data válida
     */
    protected function isDateString(string $value): bool
    {
        try {
            $date = new \DateTime($value);

            return $date !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verifica se número parece timestamp (entre 2000 e 2100)
     */
    protected function looksLikeTimestamp(float $value): bool
    {
        return $this->isBetween($value, 946684800, 4102444800);
    }
}
