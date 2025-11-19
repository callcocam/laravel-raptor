<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

use Callcocam\LaravelRaptor\Support\Cast\Formatters\NumberFormatter;

/**
 * NumericDetectionStrategy - Detecta valores numéricos variados
 */
class NumericDetectionStrategy extends AbstractDetectionStrategy
{
    protected int $priority = 40; // Baixa prioridade pois é genérico

    public function matches(mixed $value): bool
    {
        if (! is_numeric($value)) {
            return false;
        }

        $num = (float) $value;

        // Não detectar timestamps, money, percentages (outras estratégias cuidam)
        if ($this->isBetween($num, 946684800, 4102444800)) {
            return false; // Provável timestamp
        }

        if ($num > 0 && $num < 1000000 && round($num, 2) == $num) {
            return false; // Provável money
        }

        if ($this->isBetween($num, 0, 100)) {
            return false; // Provável percentage
        }

        return true;
    }

    public function getFormatter(mixed $value): ?object
    {
        $num = (float) $value;

        // Números grandes: abreviar
        if ($num >= 10000) {
            return NumberFormatter::abbreviated();
        }

        // Números com decimais
        if (is_float($value) || $num != intval($num)) {
            return NumberFormatter::decimal(2);
        }

        // Inteiros
        return NumberFormatter::decimal(0);
    }
}
