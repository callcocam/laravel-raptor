<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Columns\Types;

use Callcocam\LaravelRaptor\Support\Import\Columns\Column;

class ImportNumber extends Column
{
    protected bool $isFloat = false;

    protected int $decimals = 2;

    public function render(mixed $value, $row = null): mixed
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        // Remove separadores de milhar e substitui vírgula por ponto
        $value = str_replace(['.', ','], ['', '.'], $value);

        return $this->isFloat
            ? (float) $value
            : (int) $value;
    }

    public function float(int $decimals = 2): self
    {
        $this->isFloat = true;
        $this->decimals = $decimals;

        return $this;
    }

    public function integer(): self
    {
        $this->isFloat = false;

        return $this;
    }
}
