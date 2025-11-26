<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class DateField extends Column
{
    protected bool $isRequired = false;

    protected ?string $minDate = null;

    protected ?string $maxDate = null;

    protected string $format = 'Y-m-d';

    protected bool $withTime = false;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->type('date');
        $this->component('form-column-date');
        $this->setUp();
    } 
    
    public function minDate(string $date): self
    {
        $this->minDate = $date;

        return $this;
    }

    public function maxDate(string $date): self
    {
        $this->maxDate = $date;

        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function withTime(bool $withTime = true): self
    {
        $this->withTime = $withTime;
        if ($withTime) {
            $this->type('datetime-local');
        }

        return $this;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'required' => $this->isRequired,
            'minDate' => $this->minDate,
            'maxDate' => $this->maxDate,
            'format' => $this->format,
            'withTime' => $this->withTime,
        ]);
    }
}
