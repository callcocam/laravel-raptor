<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

use Callcocam\LaravelRaptor\Support\Cast\Formatters\CastFormatter;

/**
 * BooleanDetectionStrategy - Detecta valores booleanos
 */
class BooleanDetectionStrategy extends AbstractDetectionStrategy
{
    protected int $priority = 80;

    public function matches(mixed $value): bool
    {
        return is_bool($value);
    }

    public function getFormatter(mixed $value): ?object
    {
        return CastFormatter::boolean();
    }
}
