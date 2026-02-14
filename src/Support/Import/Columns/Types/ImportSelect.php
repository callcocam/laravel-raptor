<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Columns\Types;

use Callcocam\LaravelRaptor\Support\Import\Columns\Column;
use Closure;

class ImportSelect extends Column
{
    protected string $delimiter = ',';

    public function render(mixed $value, $row = null): mixed
    {
        if (empty($value)) {
            return $this->multiple ? [] : null;
        }

        $options = $this->getOptions();

        if ($this->multiple) {
            // Divide por delimitador e mapeia para as opções
            $values = array_map('trim', explode($this->delimiter, $value));

            return array_map(function ($v) use ($options) {
                return $this->mapValue($v, $options);
            }, $values);
        }

        return $this->mapValue($value, $options);
    }

    protected function mapValue(mixed $value, array $options): mixed
    {
        // Se o valor está nas chaves (IDs), retorna ele
        if (array_key_exists($value, $options)) {
            return $value;
        }

        // Busca pelo label (case insensitive)
        $valueLower = strtolower(trim((string) $value));

        foreach ($options as $key => $label) {
            if (strtolower($label) === $valueLower) {
                return $key;
            }
        }

        // Retorna o valor original se não encontrar
        return $value;
    }

    public function options(array|Closure $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function multiples(bool $multiple = true, string $delimiter = ','): self
    {
        $this->multiple($multiple);
        $this->delimiter = $delimiter;

        return $this;
    }
}
