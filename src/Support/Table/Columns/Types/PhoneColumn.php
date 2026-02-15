<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Table\Columns\Column;

class PhoneColumn extends Column
{
    protected ?string $component = 'table-column-phone';

    protected bool $maskEnabled = true;

    public function mask(bool $enabled = true): self
    {
        $this->maskEnabled = $enabled;

        return $this;
    }

    public function render(mixed $value, $row = null): mixed
    {
        if (empty($value)) {
            return null;
        }

        if (! $this->maskEnabled) {
            return $this->getFormattedValue($value, $row);
        }

        $formattedValue = $this->getFormattedValue(preg_replace('/[^0-9]/', '', $value), $row);

        if (strlen($formattedValue) === 11) {
            return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $formattedValue);
        }

        if (strlen($formattedValue) === 10) {
            return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $formattedValue);
        }

        return $formattedValue;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'maskEnabled' => $this->maskEnabled,
        ]);
    }
}
