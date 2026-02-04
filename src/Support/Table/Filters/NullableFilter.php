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
 * Filtro avançado para valores null, vazios, zeros e valores especiais
 * 
 * Permite controlar:
 * - Valores null/not null
 * - Valores vazios ('')/não vazios
 * - Zero (0)/maior que zero
 * - Customizações via callback
 */
class NullableFilter extends FilterBuilder
{
    use BelongsToOptions;

    protected string $component = 'filter-nullable';

    protected bool $includeZero = true;
    protected bool $includeEmpty = true;
    protected bool $includeNull = true;
    protected bool $treatZeroAsEmpty = false;
    protected bool $treatEmptyAsNull = false;

    protected ?Closure $customQuery = null;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
    }

    /**
     * Configura se deve incluir zero (0) como valor válido
     */
    public function includeZero(bool $include = true): static
    {
        $this->includeZero = $include;
        return $this;
    }

    /**
     * Configura se deve incluir strings vazias ('') como valor válido
     */
    public function includeEmpty(bool $include = true): static
    {
        $this->includeEmpty = $include;
        return $this;
    }

    /**
     * Configura se deve incluir null como valor válido
     */
    public function includeNull(bool $include = true): static
    {
        $this->includeNull = $include;
        return $this;
    }

    /**
     * Trata zero como valor vazio
     */
    public function treatZeroAsEmpty(bool $treat = true): static
    {
        $this->treatZeroAsEmpty = $treat;
        return $this;
    }

    /**
     * Trata string vazia como null
     */
    public function treatEmptyAsNull(bool $treat = true): static
    {
        $this->treatEmptyAsNull = $treat;
        return $this;
    }

    /**
     * Preset: Filtro para valores null/not null
     */
    public function nullToggle(
        string $nullLabel = 'Apenas Null',
        string $notNullLabel = 'Apenas Preenchidos',
        string $allLabel = 'Todos'
    ): static {
        $this->options = [
            ['value' => 'all', 'label' => $allLabel],
            ['value' => 'null', 'label' => $nullLabel],
            ['value' => 'not_null', 'label' => $notNullLabel],
        ];

        $this->queryUsing(function ($query, $value) {
            if ($value === 'null') {
                return $query->whereNull($this->getName());
            }
            if ($value === 'not_null') {
                return $query->whereNotNull($this->getName());
            }
            return $query;
        });

        return $this;
    }

    /**
     * Preset: Filtro para valores vazios/não vazios (inclui null)
     */
    public function emptyToggle(
        string $emptyLabel = 'Apenas Vazios',
        string $notEmptyLabel = 'Apenas Preenchidos',
        string $allLabel = 'Todos'
    ): static {
        $this->options = [
            ['value' => 'all', 'label' => $allLabel],
            ['value' => 'empty', 'label' => $emptyLabel],
            ['value' => 'not_empty', 'label' => $notEmptyLabel],
        ];

        $this->queryUsing(function ($query, $value) {
            $column = $this->getName();
            
            if ($value === 'empty') {
                return $query->where(function ($q) use ($column) {
                    $q->whereNull($column)
                      ->orWhere($column, '')
                      ->orWhere($column, 0);
                });
            }
            
            if ($value === 'not_empty') {
                return $query->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->where($column, '!=', 0);
            }
            
            return $query;
        });

        return $this;
    }

    /**
     * Preset: Filtro para valores zero/maior que zero
     */
    public function zeroToggle(
        string $zeroLabel = 'Apenas Zero',
        string $positiveLabel = 'Maior que Zero',
        string $allLabel = 'Todos'
    ): static {
        $this->options = [
            ['value' => 'all', 'label' => $allLabel],
            ['value' => 'zero', 'label' => $zeroLabel],
            ['value' => 'positive', 'label' => $positiveLabel],
        ];

        $this->queryUsing(function ($query, $value) {
            $column = $this->getName();
            
            if ($value === 'zero') {
                return $query->where($column, 0);
            }
            
            if ($value === 'positive') {
                return $query->where($column, '>', 0);
            }
            
            return $query;
        });

        return $this;
    }

    /**
     * Preset: Filtro completo com todas as opções
     */
    public function fullToggle(
        string $nullLabel = 'Null',
        string $emptyLabel = 'Vazio',
        string $zeroLabel = 'Zero',
        string $filledLabel = 'Preenchido',
        string $allLabel = 'Todos'
    ): static {
        $this->options = [
            ['value' => 'all', 'label' => $allLabel],
            ['value' => 'null', 'label' => $nullLabel],
            ['value' => 'empty', 'label' => $emptyLabel],
            ['value' => 'zero', 'label' => $zeroLabel],
            ['value' => 'filled', 'label' => $filledLabel],
        ];

        $this->queryUsing(function ($query, $value) {
            $column = $this->getName();
            
            if ($value === 'null') {
                return $query->whereNull($column);
            }
            
            if ($value === 'empty') {
                return $query->where($column, '');
            }
            
            if ($value === 'zero') {
                return $query->where($column, 0);
            }
            
            if ($value === 'filled') {
                return $query->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->where($column, '!=', 0);
            }
            
            return $query;
        });

        return $this;
    }

    /**
     * Query customizada usando callback
     */
    public function query(Closure $callback): static
    {
        $this->customQuery = $callback;
        $this->queryUsing($callback);
        return $this;
    }

    protected function setUp(): void
    {
        // Se não tiver options definidas, usa o preset padrão
        if (empty($this->options)) {
            $this->nullToggle();
        }
    }
}
