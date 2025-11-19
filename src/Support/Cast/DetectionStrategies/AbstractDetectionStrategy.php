<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

/**
 * AbstractDetectionStrategy - Classe base para estratégias de detecção
 */
abstract class AbstractDetectionStrategy implements DetectionStrategy
{
    protected int $priority = 50;

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Helper: verifica se valor está entre min e max
     */
    protected function isBetween(float $value, float $min, float $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    /**
     * Helper: verifica se valor tem exatas N casas decimais
     */
    protected function hasExactDecimals(float $value, int $decimals): bool
    {
        return round($value, $decimals) == $value;
    }

    /**
     * Helper: valida regex
     */
    protected function matchesPattern(string $value, string $pattern): bool
    {
        return preg_match($pattern, $value) === 1;
    }
}
