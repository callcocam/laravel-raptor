<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Columns;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Closure;

abstract class Column extends AbstractColumn
{
    protected Closure|string|int|float|null $defaultValue = null;

    protected Closure|string|null $format = null;

    protected Closure|string|null $cast = null;

    protected Closure|string|int|null $index = null;

    public function __construct(string $name, ?string $label = null)
    {
        $this->name($name);
        $this->label($label ?? ucwords(str_replace('_', ' ', $name)));
        $this->setUp();
    }

    abstract public function render(mixed $value, $row = null): mixed;

    public function toArray(): array
    {
        return [
            'name' => $this->getName(), // Nome do campo no banco de dados
            'label' => $this->getLabel(), // Cabeçalho folha(sheet) da planilha exel ou índice da coluna
            'index' => $this->getIndex(), // Índice da coluna na planilha (se for numérico)
            'type' => $this->getType(), // Tipo do campo, ex: text, number, date, etc
            'length' => $this->getLength(), // Tamanho do campo
            'rules' => $this->getRules(), // Regras de validação
            'default' => $this->getDefaultValue(), // Valor padrão do campo
            'format' => $this->getFormat(), // Formato de exibição (ex: 'd/m/Y')
            'cast' => $this->getCast(), // Classe de cast ou tipo primitivo
        ];
    }

    public function defaultValue(Closure|string|int|float|null $value): static
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function getDefaultValue(): Closure|string|int|float|null
    {
        return $this->evaluate($this->defaultValue);
    }

    /**
     * Define o formato de exibição/conversão do valor
     * Pode ser uma string (ex: 'd/m/Y' para datas) ou uma classe de cast
     */
    public function format(Closure|string|null $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): Closure|string|null
    {
        return $this->evaluate($this->format);
    }

    /**
     * Define uma classe de cast ou tipo primitivo para conversão
     * Ex: 'integer', 'float', 'boolean', 'datetime', ou uma classe personalizada
     */
    public function cast(Closure|string|null $cast): static
    {
        $this->cast = $cast;

        return $this;
    }

    public function getCast(): Closure|string|null
    {
        return $this->evaluate($this->cast);
    }

    /**
     * Define o índice da coluna na planilha (pode ser numérico ou string)
     * Se não definido, usa o label como referência
     */
    public function index(Closure|string|int|null $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function getIndex(): Closure|string|int|null
    {
        return $this->evaluate($this->index);
    }

    public function unique(): self
    {
        $this->rules(array_merge($this->getRules(), ['unique']));

        return $this;
    }

    /**
     * Processa e formata o valor de acordo com format e cast definidos
     */
    public function processValue(mixed $value, $row = null): mixed
    {
        // Se valor está vazio e tem default, usa o default
        if (empty($value) && $this->getDefaultValue() !== null) {
            $value = $this->getDefaultValue();
        }

        // Renderiza o valor (implementação específica de cada tipo)
        $value = $this->render($value, $row);

        // Aplica cast se definido
        if ($cast = $this->getCast()) {
            $value = $this->applyCast($value, $cast);
        }

        // Aplica formato se definido
        if ($format = $this->getFormat()) {
            $value = $this->applyFormat($value, $format);
        }

        return $value;
    }

    /**
     * Aplica cast ao valor
     */
    protected function applyCast(mixed $value, string $cast): mixed
    {
        return match ($cast) {
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'boolean', 'bool' => (bool) $value,
            'string' => (string) $value,
            'array' => (array) $value,
            'datetime', 'date' => $value instanceof \DateTime ? $value : new \DateTime($value),
            default => class_exists($cast) ? app($cast)->set(null, null, $value, []) : $value,
        };
    }

    /**
     * Aplica formato ao valor
     */
    protected function applyFormat(mixed $value, string $format): mixed
    {
        // Se for DateTime, formata conforme o padrão
        if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        // Se for string de formato de data, tenta converter
        if (is_string($format) && preg_match('/[dmYHis]/', $format)) {
            try {
                $date = new \DateTime($value);

                return $date->format($format);
            } catch (\Exception $e) {
                // Se falhar, retorna o valor original
                return $value;
            }
        }

        return $value;
    }
}
