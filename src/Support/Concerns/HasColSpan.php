<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

trait HasColSpan
{

    /**
     * Define o número de colunas que o campo irá ocupar (1-12)
     * @param int $span Número de colunas (1-12)
     * @return static
     */
    public function columnSpanOne(): static
    {
        return $this->columnSpan('1');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (2-12)
     * @param int $span Número de colunas (2-12)
     * @return static
     */
    public function columnSpanTwo(): static
    {
        return $this->columnSpan('2');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (3-12)
     * @param int $span Número de colunas (3-12)
     * @return static
     */
    public function columnSpanThree(): static
    {
        return $this->columnSpan('3');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (4-12)
     * @param int $span Número de colunas (4-12)
     * @return static
     */
    public function columnSpanFour(): static
    {
        return $this->columnSpan('4');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (5-12)
     * @param int $span Número de colunas (5-12)
     * @return static
     */
    public function columnSpanFive(): static
    {
        return $this->columnSpan('5');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (6-12)
     * @param int $span Número de colunas (6-12)
     * @return static
     */
    public function columnSpanSix(): static
    {
        return $this->columnSpan('6');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (7-12)
     * @param int $span Número de colunas (7-12)
     * @return static
     */
    public function columnSpanSeven(): static
    {
        return $this->columnSpan('7');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (8-12)
     * @param int $span Número de colunas (8-12)
     * @return static
     */
    public function columnSpanEight(): static
    {
        return $this->columnSpan('8');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (9-12)
     * @param int $span Número de colunas (9-12)
     * @return static
     */

    public function columnSpanNine(): static
    {
        return $this->columnSpan('9');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (10-12)
     * @param int $span Número de colunas (10-12)
     * @return static
     */
    public function columnSpanTen(): static
    {
        return $this->columnSpan('10');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (11-12)
     * @param int $span Número de colunas (11-12)
     * @return static
     */
    public function columnSpanEleven(): static
    {
        return $this->columnSpan('11');
    }

    /**
     * Define o número de colunas que o campo irá ocupar (12)
     * @param int $span Número de colunas (12)
     * @return static
     */
    public function columnSpanFull(): static
    {
        return $this->columnSpan('12');
    }
}
