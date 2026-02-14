<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;

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

    protected function setUp(): void
    {
        $this->queryUsing(function ($query, $value) {

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

            // Query padrão:
            // false = whereNull (apenas registros nulos)
            // true = whereNotNull (apenas registros não nulos)

            return $boolValue
                ? $query->whereNotNull($column)
                : $query->whereNull($column);
        });
    }

    /**
     * Define labels customizados para true/false
     *
     * @param  string  $trueLabel  Label quando true (whereNotNull)
     * @param  string  $falseLabel  Label quando false (whereNull)
     */
    public function labels(string $trueLabel, string $falseLabel): static
    {
        $this->trueLabel = $trueLabel;
        $this->falseLabel = $falseLabel;

        return $this;
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
