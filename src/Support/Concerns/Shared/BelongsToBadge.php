<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

trait BelongsToBadge
{
    protected bool $isBadge = false;

    /**
     * Define se o elemento deve ser renderizado como badge
     */
    public function badge(bool $condition = true): static
    {
        $this->isBadge = $condition;

        return $this;
    }

    /**
     * Verifica se o elemento é um badge
     */
    public function isBadge(): bool
    {
        return $this->isBadge;
    }

    /**
     * Obtém o status de badge
     */
    public function getBadge(): bool
    {
        return $this->isBadge;
    }
}
