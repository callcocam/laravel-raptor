<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table;

use Callcocam\LaravelRaptor\Support\Concerns\EvaluatesClosures;
use Callcocam\LaravelRaptor\Support\Concerns\FactoryPattern;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToContext;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToHelpers;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToIcon;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToId;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToLabel;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToName;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToVisible;
use Callcocam\LaravelRaptor\Support\Table\Strategies\BooleanFilterStrategy;
use Callcocam\LaravelRaptor\Support\Table\Strategies\DateFilterStrategy;
use Callcocam\LaravelRaptor\Support\Table\Strategies\ExactFilterStrategy;
use Callcocam\LaravelRaptor\Support\Table\Strategies\FilterStrategy;
use Callcocam\LaravelRaptor\Support\Table\Strategies\InFilterStrategy;
use Callcocam\LaravelRaptor\Support\Table\Strategies\LikeFilterStrategy;
use Callcocam\LaravelRaptor\Support\Table\Strategies\RangeFilterStrategy;
use Closure;

class FilterBuilder
{
    use BelongsToContext;
    use BelongsToHelpers;
    use BelongsToIcon;
    use BelongsToId;
    use BelongsToLabel;
    use BelongsToName;
    use BelongsToVisible;
    use EvaluatesClosures;
    use FactoryPattern;

    protected string $component = 'filter-text';

    protected ?Closure $applyCallback = null;

    protected $value = null;

    protected ?FilterStrategy $filterStrategy = null;

    public function __construct(string $name, ?string $label = null)
    {
        $this->label($label ?? ucfirst($name));
        $this->name($name);
        $this->id($name);
        $this->setUp();
    }

    public function queryUsing(Closure $callback)
    {
        $this->applyCallback = $callback;

        return $this;
    }

    public function setValue($value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function applyUserQuery(&$query)
    {
        $applyCallback = $this->getApplyCallback();

        if ($applyCallback) {
            return $this->evaluate($applyCallback, [
                'query' => $query,
                'value' => $this->getValue(),
            ]);
        }

        return $query;
    }

    public function apply(&$query, $value): static
    {
        $applyCallback = $this->getApplyCallback();
        if ($applyCallback) {
            $this->evaluate($applyCallback, [
                'query' => $query,
                'value' => $value,
            ]);
        }

        return $this;
    }

    public function getApplyCallback(): ?Closure
    {
        return $this->applyCallback;
    }

    /**
     * Define estratégia de filtro customizada
     */
    public function strategy(FilterStrategy $strategy): static
    {
        $this->filterStrategy = $strategy;

        return $this;
    }

    /**
     * Obtém estratégia de filtro (default: Like)
     */
    public function getStrategy(): FilterStrategy
    {
        return $this->filterStrategy ?? new LikeFilterStrategy;
    }

    /**
     * Métodos fluentes para estratégias comuns
     */
    public function exact(): static
    {
        return $this->strategy(new ExactFilterStrategy);
    }

    public function like(): static
    {
        return $this->strategy(new LikeFilterStrategy);
    }

    public function in(): static
    {
        return $this->strategy(new InFilterStrategy);
    }

    public function range(mixed $min = null, mixed $max = null): static
    {
        $strategy = new RangeFilterStrategy;

        if ($min !== null || $max !== null) {
            $this->value = array_filter([
                'min' => $min,
                'max' => $max,
            ], fn ($v) => $v !== null);
        }

        return $this->strategy($strategy);
    }

    public function date(): static
    {
        return $this->strategy(new DateFilterStrategy);
    }

    public function boolean(): static
    {
        return $this->strategy(new BooleanFilterStrategy);
    }

    protected function setUp(): void
    {
        //
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'component' => $this->component,
            'context' => $this->getContext(),
            'strategy' => $this->filterStrategy?->getName(),
            'visible' => $this->isVisible(),
            'placeholder' => $this->getPlaceholder(),
        ];
    }
}
