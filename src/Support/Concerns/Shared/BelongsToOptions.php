<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToOptions
{
    /**
     * The options for the filter.
     */
    protected array $options = [];

    protected Closure|bool|null $multiple = null;

    /**
     * Set the options for the filter.
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get the options for the filter.
     */
    public function getOptions(): array
    {
        return $this->evaluate($this->options);
    }

    public function multiple(bool|Closure $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->multiple);
    }
}
