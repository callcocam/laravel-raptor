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

    /**
     * Alias para castFormat() - aplica o formatador ao valor
     */
    public function value($castCallback)
    {
        return $this->castFormat($castCallback);
    }

    /**
     * Obtém o callback de cast
     */
    public function getCastCallback($value = null, $data = null)
    {
        return $this->evaluate($this->castCallback, ['value' => $value, 'row' => $data]);
    }

    /**
     * Verifica se há um callback de cast definido
     */
    public function hasCastCallback(): bool
    {
        return $this->castCallback !== null;
    }
}
