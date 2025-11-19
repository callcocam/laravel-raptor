<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToId
{
    /**
     * The ID of the column.
     *
     * @var string|null
     */
    public Closure|string|null $id = null;

    /**
     * Set the ID for the column.
     *
     * @param  string  $id
     */
    public function id(Closure|string|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the ID of the column.
     */
    public function getId(): ?string
    {
        return $this->evaluate($this->id);
    }

    /**
     * Has an ID been set for the column?
     */
    public function hasId(): bool
    {
        return ! is_null($this->id);
    }
}
