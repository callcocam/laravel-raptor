<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtro toggle para valores null/not null
 * 
 * Comportamento:
 * - false: Filtra apenas registros NULL (whereNull)
 * - true: Filtra apenas registros NOT NULL (whereNotNull)
 * - null/undefined: Não aplica filtro (todos registros)
 * 
 * Uso:
 * NullableFilter::make('status')
 * NullableFilter::make('deleted_at')->labels('Ativo', 'Deletado')
 * NullableFilter::make('amount')->query(fn($q, $value, $column) => ...)
 */
class NullableFilter extends FilterBuilder
{
    protected string $component = 'filter-nullable';

    protected string $trueLabel = 'Not Null';
    protected string $falseLabel = 'Null';

    protected ?Closure $customQuery = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
    }

    /**
     * Define labels customizados para true/false
     * 
     * @param string $trueLabel Label quando true (whereNotNull)
     * @param string $falseLabel Label quando false (whereNull)
     */
    public function labels(string $trueLabel, string $falseLabel): static
    {
        $this->trueLabel = $trueLabel;
        $this->falseLabel = $falseLabel;
        return $this;
    }

    /**
     * Define uma query customizada
     * 
     * @param Closure $callback Callback (Builder $query, bool $value, string $column)
     */
    public function query(Closure $callback): static
    {
        $this->customQuery = $callback;
        return $this;
    }

    /**
     * Aplica o filtro na query
     */
    public function apply(&$query, $value): static
    {
        // Se valor for null ou string vazia, não aplica filtro
        if ($value === null || $value === '') {
            return $query;
        }

        // Converte string para boolean se necessário
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($boolValue === null) {
            return $query;
        }

        $column = $this->getName();

        // Se tem query customizada, usa ela
        if ($this->customQuery) {
            return call_user_func($this->customQuery, $query, $boolValue, $column);
        }

        // Query padrão:
        // false = whereNull (apenas registros nulos)
        // true = whereNotNull (apenas registros não nulos)
        return $boolValue
            ? $query->whereNotNull($column)
            : $query->whereNull($column);
    }

    /**
     * Serializa o filtro para array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['trueLabel'] = $this->trueLabel;
        $array['falseLabel'] = $this->falseLabel;
        return $array;
    }
}
