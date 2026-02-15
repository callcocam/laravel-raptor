<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Table\Columns\Column;
use Carbon\Carbon;

class DateColumn extends Column
{
    protected ?string $component = 'table-column-date';

    protected ?string $format = null;

    protected bool $relative = false;

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function relative(bool $relative = true): self
    {
        $this->relative = $relative;

        return $this;
    }

    public function render(mixed $value, $row = null): mixed
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value);

            if ($this->relative) {
                return $this->getFormattedValue($date->diffForHumans(), $row);
            }

            if ($this->format) {
                return $date->format($this->format);
            }

            return $date->format('d/m/Y H:i');
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'format' => $this->format,
            'relative' => $this->relative,
        ]);
    }
}
