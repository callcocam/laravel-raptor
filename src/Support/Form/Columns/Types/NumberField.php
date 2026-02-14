<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class NumberField extends Column
{
    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected ?float $min = null;

    protected ?float $max = null;

    protected ?float $step = null;

    protected ?array $calculation = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->type('number');
        $this->component('form-field-number');
        $this->setUp();
    }

    public function min(float $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function max(float $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function step(float $step): self
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Define uma soma de campos
     */
    public function sum(array $fields): static
    {
        $this->calculation = [
            'type' => 'sum',
            'fields' => $fields,
        ];

        $this->readonly();

        return $this;
    }

    /**
     * Define uma subtração de campos
     */
    public function subtract(string $minuend, array $subtrahends): static
    {
        $this->calculation = [
            'type' => 'subtract',
            'minuend' => $minuend,
            'subtrahends' => $subtrahends,
        ];

        $this->readonly();

        return $this;
    }

    /**
     * Define uma multiplicação de campos
     */
    public function multiply(array $fields): static
    {
        $this->calculation = [
            'type' => 'multiply',
            'fields' => $fields,
        ];

        $this->readonly();

        return $this;
    }

    /**
     * Define uma divisão de campos
     */
    public function divide(string $dividend, string $divisor): static
    {
        $this->calculation = [
            'type' => 'divide',
            'dividend' => $dividend,
            'divisor' => $divisor,
        ];

        $this->readonly();

        return $this;
    }

    /**
     * Define uma média de campos
     */
    public function average(array $fields): static
    {
        $this->calculation = [
            'type' => 'average',
            'fields' => $fields,
        ];

        $this->readonly();

        return $this;
    }

    /**
     * Define valor mínimo entre campos
     */
    public function min_calc(array $fields): static
    {
        $this->calculation = [
            'type' => 'min',
            'fields' => $fields,
        ];

        $this->readonly();

        return $this;
    }

    /**
     * Define valor máximo entre campos
     */
    public function max_calc(array $fields): static
    {
        $this->calculation = [
            'type' => 'max',
            'fields' => $fields,
        ];

        $this->readonly();

        return $this;
    }

    /**
     * Define cálculo de porcentagem
     */
    public function percentage(string $value, float|string $percentage): static
    {
        $this->calculation = [
            'type' => 'percentage',
            'value' => $value,
            'percentage' => $percentage,
        ];

        $this->readonly();

        return $this;
    }

    /**
     * Define um cálculo customizado
     */
    public function calculate(string $expression, array $fields = []): static
    {
        $this->calculation = [
            'type' => 'custom',
            'expression' => $expression,
            'fields' => $fields,
        ];

        $this->readonly();

        return $this;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'required' => $this->isRequired,
            'placeholder' => $this->placeholder,
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
            'calculation' => $this->calculation,
        ]);
    }
}
