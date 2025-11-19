<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToName
{
    /**
     * The name of the column.
     *
     * @var string|null
     */
    public Closure|string|null $name = null;

    /**
     * Set the name for the column.
     *
     * @param  string  $name
     */
    public function name(Closure|string|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name of the column.
     */
    public function getName(): ?string
    {
        return $this->evaluate($this->name);
    }

    /**
     * Has a name been set for the column?
     */
    public function hasName(): bool
    {
        return ! is_null($this->name);
    }
}
