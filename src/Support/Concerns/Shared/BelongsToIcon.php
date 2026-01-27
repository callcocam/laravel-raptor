<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToIcon
{
    protected Closure|string|null $icon = 'Settings';

    /**
     * Define o ícone da ação
     */
    public function icon(Closure|string|null $icon, string $position = 'left'): static
    {
        $this->icon = $icon;

        if ($position === 'right' && method_exists($this, 'append')) {
            $this->append($icon);
        } else {
            if (method_exists($this, 'prepend')) {
                $this->prepend($icon);
            }
        }

        return $this;
    }

    /**
     * Obtém o ícone da ação
     */
    public function getIcon(): string
    {
        return $this->evaluate($this->icon);
    }

    /**
     * Verifica se o ícone existe
     */
    public function hasIcon(): bool
    {
        return ! empty($this->icon);
    }

    /**
     * Retorna o ícone como string
     */
    public function toStringIcon(): string
    {
        return is_string($this->icon) ? $this->icon : '';
    }
}
