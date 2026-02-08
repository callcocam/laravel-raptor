<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Columns;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Import\Contracts\ImportCastInterface;
use Closure;

abstract class Column extends AbstractColumn
{
    protected Closure|string|int|float|null $defaultValue = null;

    protected bool $hidden = false;

    protected ?string $sheetName = null;

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

    /**
     * Sobrescreve o label para normalizar da mesma forma que o Laravel Excel faz
     */
    public function label(Closure|string|null $label): static
    {
        if (is_string($label)) {
            $label = $this->normalizeLabel($label);
        }

        return parent::label($label);
    }

    /**
     * Normaliza o label da mesma forma que o Laravel Excel normaliza os cabeçalhos
     * - Converte para lowercase
     * - Remove acentos
     * - Substitui espaços por underscore
     * - Remove caracteres especiais
     */
    protected function normalizeLabel(string $label): string
    {
        // Remove acentos
        $label = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);

        // Lowercase
        $label = mb_strtolower($label, 'UTF-8');

        // Substitui espaços e caracteres especiais por underscore
        $label = preg_replace('/[^a-z0-9]+/', '_', $label);

        // Remove underscores no início e no fim
        $label = trim($label, '_');

        return $label;
    }

    public function toArray(): array
    {
        $default = $this->getDefaultValue();
        // Closures não são serializáveis para o Job; defaults de contexto (tenant_id, user_id) vêm via setContext()
        $defaultSerializable = $default instanceof \Closure ? null : $default;

        return [
            'class' => static::class, // Para reconstruir a coluna no Job (serialização)
            'name' => $this->getName(), // Nome do campo no banco de dados
            'label' => $this->getLabel(), // Cabeçalho folha(sheet) da planilha exel ou índice da coluna
            'index' => $this->getIndex(), // Índice da coluna na planilha (se for numérico)
            'type' => $this->getType(), // Tipo do campo, ex: text, number, date, etc
            'length' => $this->getLength(), // Tamanho do campo
            'rules' => $this->getRules(), // Regras de validação
            'default' => $defaultSerializable, // Valor padrão (Closure omitido; usar context no Job)
            'format' => $this->getFormat(), // Formato de exibição (ex: 'd/m/Y')
            'cast' => $this->getCast(), // Classe de cast ou tipo primitivo
            'hidden' => $this->isHidden(), // Campo invisível (não vem do Excel)
            'sheet' => $this->getSheetName(), // Nome da sheet de origem
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
     * Marca a coluna como hidden (não vem do Excel; usa defaultValue).
     */
    public function hidden(bool|\Closure|null $hidden = true): static
    {
        $this->hidden = $hidden === true;

        return parent::hidden($hidden);
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Define a sheet de origem para a coluna
     */
    public function sheet(string $sheetName): static
    {
        $this->sheetName = $sheetName;

        return $this;
    }

    public function getSheetName(): ?string
    {
        return $this->sheetName;
    }

    /**
     * Processa e formata o valor de acordo com format e cast definidos
     */
    public function processValue(mixed $value, $row = null): mixed
    {
        // Renderiza o valor (implementação específica de cada tipo)
        $value = $this->render($value, $row);

        // Se valor está vazio/null após render e tem default, usa o default
        if (($value === null || $value === '') && $this->getDefaultValue() !== null) {
            $value = $this->getDefaultValue();
        }

        // Aplica cast se definido (classes podem implementar ImportCastInterface com format(name, label, value, row))
        if ($cast = $this->getCast()) {
            $value = $this->applyCast($value, $cast, is_array($row) ? $row : []);
        }

        // Aplica formato se definido
        if ($format = $this->getFormat()) {
            $value = $this->applyFormat($value, $format);
        }

        return $value;
    }

    /**
     * Aplica cast ao valor.
     * Se a cast for uma classe que implementa ImportCastInterface, chama format(name, label, value, row).
     *
     * @param  array<string, mixed>  $row  Linha completa (para casts que precisam do contexto)
     */
    protected function applyCast(mixed $value, string $cast, array $row = []): mixed
    {
        $primitives = match ($cast) {
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'boolean', 'bool' => (bool) $value,
            'string' => (string) $value,
            'array' => (array) $value,
            'datetime', 'date' => $value instanceof \DateTime ? $value : new \DateTime($value),
            default => null,
        };

        if ($primitives !== null) {
            return $primitives;
        }

        if (! class_exists($cast)) {
            return $value;
        }

        $instance = app($cast);

        if ($instance instanceof ImportCastInterface) {
            return $instance->format(
                $this->getName(),
                (string) $this->getLabel(),
                $value,
                $row
            );
        }

        return $value;
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
