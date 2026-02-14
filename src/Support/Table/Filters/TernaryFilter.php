<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToOptions;
use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;
use Closure;

/**
 * Filtro ternário com três estados: null (todos), true, false
 * Útil para filtros que precisam de três opções mutuamente exclusivas
 */
class TernaryFilter extends FilterBuilder
{
    use BelongsToOptions;

    protected string $component = 'filter-select';

    protected ?string $placeholderLabel = null;

    protected ?string $trueLabel = null;

    protected ?string $falseLabel = null;

    protected ?Closure $trueQuery = null;

    protected ?Closure $falseQuery = null;

    protected ?Closure $nullQuery = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
    }

    /**
     * Define o label do placeholder (opção "todos")
     */
    public function placeholder(string $label): static
    {
        $this->placeholderLabel = $label;

        return $this;
    }

    /**
     * Define o label para o estado "true"
     */
    public function trueLabel(string $label): static
    {
        $this->trueLabel = $label;

        return $this;
    }

    /**
     * Define o label para o estado "false"
     */
    public function falseLabel(string $label): static
    {
        $this->falseLabel = $label;

        return $this;
    }

    /**
     * Define as queries para cada estado usando named arguments
     *
     * @example
     * ->queries(
     *     true: fn($query) => $query->whereNull('deleted_at'),
     *     false: fn($query) => $query->whereNotNull('deleted_at'),
     *     null: fn($query) => $query // opcional
     * )
     */
    public function queries(
        ?Closure $true = null,
        ?Closure $false = null,
        ?Closure $null = null
    ): static {
        $this->trueQuery = $true;
        $this->falseQuery = $false;
        $this->nullQuery = $null;

        return $this;
    }

    protected function setUp(): void
    {
        // Configura as opções baseadas nos labels
        $this->options = [
            ['value' => 'all', 'label' => $this->placeholderLabel ?? 'Todos'],
            ['value' => '1', 'label' => $this->trueLabel ?? 'Sim'],
            ['value' => '0', 'label' => $this->falseLabel ?? 'Não'],
        ];

        // Configura a query
        $this->queryUsing(function ($query, $value) {
            // Valor 'all' ou vazio ou null = não aplica filtro (ou aplica nullQuery se definida)
            if ($value === 'all' || $value === '' || $value === null) {
                if ($this->nullQuery) {
                    return call_user_func($this->nullQuery, $query);
                }

                return;
            }

            // Valor '1' = true
            if ($value === '1' || $value === 1 || $value === true) {
                if ($this->trueQuery) {
                    return call_user_func($this->trueQuery, $query);
                }

                return;
            }

            // Valor '0' = false
            if ($value === '0' || $value === 0 || $value === false) {
                if ($this->falseQuery) {
                    return call_user_func($this->falseQuery, $query);
                }

                return;
            }
        });
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->getOptions(),
            'placeholder' => $this->placeholderLabel,
            'trueLabel' => $this->trueLabel,
            'falseLabel' => $this->falseLabel,
        ]);
    }
}
