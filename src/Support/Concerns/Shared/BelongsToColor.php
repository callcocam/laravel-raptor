<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToColor
{
    protected Closure|string|null $color = null;

    /**
     * Define a cor do elemento
     */
    public function color(Closure|string|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Obtém a cor do elemento
     */
    public function getColor(): ?string
    {
        return $this->evaluate($this->color, [
            'model' => $this->getRecord(),
            'value' => data_get($this->getRecord(), $this->getName()),
        ]);
    }

    /**
     * Verifica se a cor existe
     */
    public function hasColor(): bool
    {
        return ! empty($this->color);
    }
}
