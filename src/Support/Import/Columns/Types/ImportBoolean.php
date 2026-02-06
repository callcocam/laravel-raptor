<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Columns\Types;

use Callcocam\LaravelRaptor\Support\Import\Columns\Column;

class ImportBoolean extends Column
{
    protected array $trueValues = ['true', '1', 'sim', 'yes', 's', 'y', 'verdadeiro'];

    protected array $falseValues = ['false', '0', 'não', 'nao', 'no', 'n', 'falso'];

    public function render(mixed $value, $row = null): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        if (in_array($value, $this->trueValues, true)) {
            return true;
        }

        if (in_array($value, $this->falseValues, true)) {
            return false;
        }

        return (bool) $value;
    }

    public function trueValues(array $values): self
    {
        $this->trueValues = array_map('strtolower', $values);

        return $this;
    }

    public function falseValues(array $values): self
    {
        $this->falseValues = array_map('strtolower', $values);

        return $this;
    }
}
