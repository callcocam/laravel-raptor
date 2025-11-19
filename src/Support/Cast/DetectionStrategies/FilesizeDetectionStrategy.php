<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

use Callcocam\LaravelRaptor\Support\Cast\Formatters\NumberFormatter;

/**
 * FilesizeDetectionStrategy - Detecta tamanhos de arquivo
 */
class FilesizeDetectionStrategy extends AbstractDetectionStrategy
{
    protected int $priority = 55;

    public function matches(mixed $value): bool
    {
        // String formatada (ex: "1.5 MB")
        if (is_string($value)) {
            return $this->matchesPattern($value, '/^\d+([.,]\d+)?\s?(B|KB|MB|GB|TB)$/i');
        }

        // Número de bytes (inteiro grande)
        if (is_numeric($value)) {
            $num = (float) $value;

            return $num >= 1024 && $num == round($num);
        }

        return false;
    }

    public function getFormatter(mixed $value): ?object
    {
        return NumberFormatter::filesize();
    }
}
