<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

use Closure;

trait HasMak
{
    protected Closure|string|null $mask = null;

    /**
     * Define a máscara para o campo de entrada
     *
     * @param  string  $mask  A máscara a ser aplicada
     * @return static
     */
    public function phone($mask = null): self
    {
        if ($mask) {
            $this->mask = $mask;
        } else {
            $this->mask = '(##) #####-####';
        }
        $this->component('form-field-mask');

        return $this;
    }

    /**
     * Define a máscara para o campo de entrada como CPF
     *
     * @return static
     */
    public function cpf(): self
    {
        $this->mask = '###.###.###-##';
        $this->component('form-field-mask');

        return $this;
    }

    /**
     * Define a máscara para o campo de entrada como CNPJ
     *
     * @return static
     */
    public function cnpj(): self
    {
        $this->mask = '##.###.###/####-##';
        $this->component('form-field-mask');

        return $this;
    }

    /**
     * Obtém a máscara definida para o campo
     */
    public function getMask(): Closure|string|null
    {
        return $this->mask;
    }

    /**
     * Verifica se uma máscara foi definida para o campo
     */
    public function hasMak(): bool
    {
        return ! is_null($this->mask);
    }

    /**
     * Limpa a máscara do valor fornecido
     *
     * @param  string|null  $value  O valor a ser limpo
     * @return string|null O valor limpo ou null se o valor for nulo
     */
    public function clearMak(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return preg_replace('/\D/', '', $value);
    }
}
