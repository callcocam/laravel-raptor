<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

trait BelongsToLimit
{
    protected ?int $characterLimit = null;

    /**
     * Define o limite de caracteres para exibição do valor
     */
    public function limit(int $characters): static
    {
        $this->characterLimit = $characters;

        return $this;
    }

    /**
     * Obtém o limite de caracteres
     */
    public function getLimit(): ?int
    {
        return $this->characterLimit;
    }

    /**
     * Verifica se tem limite definido
     */
    public function hasLimit(): bool
    {
        return $this->characterLimit !== null;
    }

    /**
     * Aplica o limite ao valor se definido
     */
    protected function applyLimit(string $value): string
    {
        if (! $this->hasLimit()) {
            return $value;
        }

        if (mb_strlen($value) <= $this->characterLimit) {
            return $value;
        }

        return mb_substr($value, 0, $this->characterLimit).'...';
    }
}
