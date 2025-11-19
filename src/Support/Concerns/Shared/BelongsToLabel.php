<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToLabel
{
    /**
     * The label of the column.
     *
     * @var string|null
     */
    public Closure|string|null $label = null;

    /**
     * Set the label for the column.
     *
     * @param  string  $label
     */
    public function label(Closure|string|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the label of the column.
     */
    public function getLabel(): ?string
    {
        return $this->evaluate($this->label);
    }

    /**
     * Has a label been set for the column?
     */
    public function hasLabel(): bool
    {
        return ! is_null($this->label);
    }
}
