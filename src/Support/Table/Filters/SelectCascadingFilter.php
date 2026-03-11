<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToFields;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToOptions;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\ResolvesCascadingOptions;
use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class SelectCascadingFilter extends FilterBuilder
{
    use BelongsToFields;
    use BelongsToOptions;
    use ResolvesCascadingOptions;

    protected ?string $component = 'filter-select-cascading';

    /** Query para alimentar as opções dos níveis (queryUsing no filter é para aplicar o filtro). */
    protected Closure|string|Builder|\Illuminate\Database\Query\Builder|null $queryUsingCascading = null;

    /** Callback customizado para resolver níveis (igual CascadingField). */
    protected ?Closure $queryUsingCallback = null;

    /**
     * Se true, o usuário pode optar por filtrar incluindo categorias pai (request envia array).
     * Se false (default), filtra só pelo último nível selecionado.
     */
    protected bool $includeParentsOption = false;

    /** Nome do parâmetro na request que guarda a flag "incluir pais" (ex: category_id_include_parents). */
    protected ?string $includeParentsParam = null;

    /**
     * Nomes dos campos que contêm IDs de nível (para whereIn). Null = usa todos os getFields().
     * Use quando houver campos que não são nível (ex.: TernaryFilter "parents") para não incluir no array.
     *
     * @var array<int, string>|null
     */
    protected ?array $levelFieldNames = null;

    protected function setUp(): void
    {
        $this->queryUsing(function ($query, $value) {
            if ($value === null || $value === '') {
                return;
            }
            $column = $this->getFieldsUsing() ?? $this->getName();
            // Quando ModelSource envia array (modo "incluir pais"), aplica whereIn; senão where.
            if (is_array($value)) {
                $value = array_filter($value, fn ($v) => $v !== null && $v !== '');
                if ($value !== []) {
                    $query->whereIn($column, $value);
                }
            } else {
                $query->where($column, $value);
            }
        });
    }

    /**
     * Habilita a opção "Incluir categorias pai" no frontend.
     * Quando ativa, o ModelSource envia todos os níveis selecionados (array) e o filtro usa whereIn.
     */
    public function includeParentsOption(bool $value = true): static
    {
        $this->includeParentsOption = $value;
        $this->includeParentsParam = $value ? $this->getName().'_include_parents' : null;

        return $this;
    }

    public function getIncludeParentsParam(): ?string
    {
        return $this->includeParentsParam;
    }

    public function hasIncludeParentsOption(): bool
    {
        return $this->includeParentsOption;
    }

    /**
     * Define quais campos são níveis (IDs) para o valor do filtro. Null = todos os fields.
     * Útil quando há campos que não são ID (ex.: TernaryFilter) no meio da cascata.
     *
     * @param  array<int, string>|null  $names
     */
    public function levelFieldNames(?array $names): static
    {
        $this->levelFieldNames = $names;

        return $this;
    }

    public function getLevelFieldNames(): ?array
    {
        return $this->levelFieldNames;
    }

    /**
     * Query para alimentar as opções dos níveis (queryUsing no filter é para aplicar o filtro na query).
     */
    public function queryUsingCascading(Closure|string|Builder|\Illuminate\Database\Query\Builder|null $query): static
    {
        $this->queryUsingCascading = $query;

        return $this;
    }

    public function getQueryUsingCascading(mixed $context = null): Closure|string|Builder|\Illuminate\Database\Query\Builder|null
    {
        $q = $this->queryUsingCascading;
        if (is_string($q)) {
            return app($q);
        }
        if ($q instanceof Builder || $q instanceof \Illuminate\Database\Query\Builder) {
            return $q;
        }
        if ($q instanceof Closure) {
            $result = $this->evaluate($q, ['context' => $context]);

            return $result instanceof Builder ? $result : null;
        }

        return null;
    }

    public function queryUsingCallback(?Closure $callback): static
    {
        $this->queryUsingCallback = $callback;

        return $this;
    }

    /**
     * Define como o filtro é aplicado na query (customizar o default).
     */
    public function applyUsing(Closure $callback): static
    {
        $this->applyCallback = $callback;

        return $this;
    }

    protected function getCascadingQuery(mixed $context): ?Builder
    {
        $q = $this->getQueryUsingCascading($context);

        return $q instanceof Builder ? $q : null;
    }

    protected function getCascadingQueryCallback(): ?Closure
    {
        return $this->queryUsingCallback;
    }

    public function order(?int $order = null): static
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function toArray(): array
    {
        $context = request()->query();
        $resolved = $this->resolveCascadingOptionsToArray($context);

        if (empty($resolved) && $this->getOptions() !== []) {
            $resolved = [
                [
                    'name' => $this->getName(),
                    'label' => $this->getLabel(),
                    'dependsOn' => null,
                    'options' => $this->getOptions(),
                ],
            ];
        }

        $fieldsArray = array_map(function (array $item) {
            return [
                'name' => $item['name'],
                'label' => $item['label'],
                'dependsOn' => $item['dependsOn'],
                'options' => $this->normalizeOptions($item['options'] ?? []),
            ];
        }, $resolved);

        return array_merge(parent::toArray(), [
            'fieldsUsing' => $this->getFieldsUsing(),
            'fields' => $fieldsArray,
            'includeParentsOption' => $this->includeParentsOption,
            'includeParentsParam' => $this->getIncludeParentsParam(),
        ]);
    }
}
