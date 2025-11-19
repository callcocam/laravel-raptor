<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table;

use Callcocam\LaravelRaptor\Support\Concerns;
use Callcocam\LaravelRaptor\Support\Concerns\EvaluatesClosures;
use Callcocam\LaravelRaptor\Support\Concerns\FactoryPattern;
use Closure;

class FilterBuilder
{
    use Concerns\Share\BelongsToContext;
    use Concerns\Share\BelongsToIcon;
    use Concerns\Share\BelongsToId;
    use Concerns\Share\BelongsToLabel;
    use Concerns\Share\BelongsToName;
    use EvaluatesClosures;
    use FactoryPattern;

    protected string $component = 'filter-text';

    protected ?Closure $applyCallback = null;

    protected $value = null;

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
                'value' => $this->getValue()
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
                'value' => $value
            ]);
        }

        return $this;
    }

    public function getApplyCallback(): ?Closure
    {
        return $this->applyCallback;
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
        ];
    }
}
