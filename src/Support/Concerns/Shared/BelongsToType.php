<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

/**
 * @property Closure|string|null $type
 */
trait BelongsToType
{
    /**
     * Set the type for the column.
     *
     * @param  string  $type
     */
    public function type(Closure|string|null $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the type for the column.
     *
     * @param  string  $type
     */
    public function setType(Closure|string|null $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the type of the column.
     */
    public function getType(): ?string
    {
        if (! $this->hasType()) {
            return null;
        }

        return $this->evaluate($this->type);
    }

    /**
     * Has an ID been set for the column?
     */
    public function hasType(): bool
    {
        if (isset($this->type)) {
            return ! is_null($this->type);
        }

        return false;
    }
}
