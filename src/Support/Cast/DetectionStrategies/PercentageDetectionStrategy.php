<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

use Callcocam\LaravelRaptor\Support\Cast\Formatters\NumberFormatter;

/**
 * PercentageDetectionStrategy - Detecta valores percentuais
 */
class PercentageDetectionStrategy extends AbstractDetectionStrategy
{
    protected int $priority = 65;

    public function matches(mixed $value): bool
    {
        // String com símbolo %
        if (is_string($value)) {
            return $this->matchesPattern($value, '/^\d+([.,]\d+)?%$/');
        }

        // Número que parece percentual
        if (is_numeric($value)) {
            $num = (float) $value;
            // Entre 0 e 1 (decimal) ou 0 e 100 (inteiro)
            return $this->isBetween($num, 0, 1) ||
                   ($this->isBetween($num, 0, 100) && $num == round($num));
        }

        return false;
    }

    public function getFormatter(mixed $value): ?object
    {
        return NumberFormatter::percentage();
    }
}
