<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

/**
 * Trait HasRowSpan
 *
 * Define o número de linhas (rows) que o campo irá ocupar.
 * Contraparte de HasColSpan para spans verticais (ex: tabelas HTML com rowspan).
 */
trait HasRowSpan
{
    /**
     * Define o número de linhas que o campo irá ocupar (1)
     */
    public function rowSpanOne(): static
    {
        return $this->rowSpan('1');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (2)
     */
    public function rowSpanTwo(): static
    {
        return $this->rowSpan('2');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (3)
     */
    public function rowSpanThree(): static
    {
        return $this->rowSpan('3');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (4)
     */
    public function rowSpanFour(): static
    {
        return $this->rowSpan('4');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (5)
     */
    public function rowSpanFive(): static
    {
        return $this->rowSpan('5');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (6)
     */
    public function rowSpanSix(): static
    {
        return $this->rowSpan('6');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (7)
     */
    public function rowSpanSeven(): static
    {
        return $this->rowSpan('7');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (8)
     */
    public function rowSpanEight(): static
    {
        return $this->rowSpan('8');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (9)
     */
    public function rowSpanNine(): static
    {
        return $this->rowSpan('9');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (10)
     */
    public function rowSpanTen(): static
    {
        return $this->rowSpan('10');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (11)
     */
    public function rowSpanEleven(): static
    {
        return $this->rowSpan('11');
    }

    /**
     * Define o número de linhas que o campo irá ocupar (12)
     */
    public function rowSpanFull(): static
    {
        return $this->rowSpan('12');
    }
}
