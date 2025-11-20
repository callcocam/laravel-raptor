<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class DateColumn extends Column
{
    protected ?string $component = 'info-column-date';

    protected string $format = 'd/m/Y';

    public function __construct($name, $label = null)
    {
        parent::__construct($name, $label);

        $this->icon('Calendar');
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function render(mixed $value, $row = null): mixed
    {
        if ($value === null) {
            return $this->getDefault() ?? '-';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->format);
        }

        try {
            return \Carbon\Carbon::parse($value)->format($this->format);
        } catch (\Exception $e) {
            return $value;
        }
    }
}
