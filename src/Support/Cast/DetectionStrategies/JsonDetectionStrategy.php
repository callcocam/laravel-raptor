<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

use Callcocam\LaravelRaptor\Support\Cast\Formatters\CastFormatter;

/**
 * JsonDetectionStrategy - Detecta valores JSON/array/object
 */
class JsonDetectionStrategy extends AbstractDetectionStrategy
{
    protected int $priority = 75;

    public function matches(mixed $value): bool
    {
        // Arrays e objetos
        if (is_array($value) || is_object($value)) {
            return true;
        }

        // String JSON
        if (is_string($value)) {
            return $this->isJsonString($value);
        }

        return false;
    }

    public function getFormatter(mixed $value): ?object
    {
        return CastFormatter::json();
    }

    /**
     * Verifica se string é JSON válido
     */
    protected function isJsonString(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        // Verifica se começa com [ ou {
        if (! in_array($value[0], ['[', '{'])) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
