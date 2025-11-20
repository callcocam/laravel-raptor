<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

use Closure;

/**
 * Trait HasCastCallbackFormatter
 *
 * Adiciona suporte para formatadores baseados em callbacks de cast
 */
trait HasCastCallbackFormatter
{
    protected ?Closure $castCallback = null;

    /**
     * Aplica o formatador ao valor
     */
    public function castFormat($castCallback)
    {
        $this->castCallback = $castCallback;

        return $this;
    }
}
