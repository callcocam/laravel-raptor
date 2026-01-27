<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns\HasGridLayout;
use Callcocam\LaravelRaptor\Support\Concerns\HasMak;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithActions;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToHelpers;
use Illuminate\Database\Eloquent\Builder;
use Closure;

abstract class Column extends AbstractColumn
{
    use BelongsToHelpers;
    use HasGridLayout;
    use HasMak;
    use WithActions;

    protected string $type = 'text';

    protected ?string $component = 'form-field-text';

    protected Closure|null $valueUsing = null;

    protected ?Closure $defaultUsing = null;

    protected Closure|string|Builder|null $queryUsing = null;

    protected int $index = 0;


    public function __construct($name, $label = null)
    {
        $this->name($name);
        $this->id($name);
        $this->label($label ?? ucfirst($name));
        $this->columnSpanFull();
        $this->index++;
        $this->valueUsing(function ($request, $model) {
            if ($this->hasMak()) {
                return [
                    $this->getName() => $this->clearMak(data_get($request, $this->getName())),
                ];
            }
            return null;
        });
    }


    public function valueUsing(Closure $callback): static
    {
        $this->valueUsing = $callback;

        return $this;
    }

    public function getValueUsing($request = null, $model = null)
    {
        return $this->evaluate($this->valueUsing, [
            'request' => $request,
            'data' => $request,
            'model' => $model,
        ]);
    }

    public function defaultUsing(Closure $callback): static
    {
        $this->defaultUsing = $callback;

        return $this;
    }

    public function getDefaultUsing($request = null, $model = null)
    {
        return $this->evaluate($this->defaultUsing, [
            'request' => $request,
            'data' => $request,
            'model' => $model,
        ]);
    }

    public function hasDefaultUsing(): bool
    {
        return !is_null($this->defaultUsing);
    }

    public function queryUsing(Closure|string|Builder|null $queryUsing): self
    {
        $this->queryUsing = $queryUsing;

        return $this;
    }


    public function getQueryUsing(): Closure|string|Builder|null
    {
        if (is_string($this->queryUsing)) {
            return app($this->queryUsing);
        }
        if ($this->queryUsing instanceof Builder) {
            return $this->queryUsing;
        }
        return $this->evaluate($this->queryUsing);
    }

    public function index(int $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }


    public function iconRight(string|Closure|null $icon): static
    {
        return $this->icon($icon, 'right');
    }

    public function iconLeft(string|Closure|null $icon): static
    {
        return $this->icon($icon, 'left');
    }

    public function toArray($model = null): array
    {
        if ($model) {
            $this->record($model);
        }
        return array_merge([
            'name' => $this->getName(),
            'type' => $this->getType(),
            'label' => $this->getLabel(),
            'default' => $this->getDefault(),
            'helpText' => $this->getHelpText(),
            'hint' => $this->getHint(),
            'prepend' => $this->getPrepend(),
            'append' => $this->getAppend(),
            'prefix' => $this->getPrefix(),
            'suffix' => $this->getSuffix(),
            'component' => $this->getComponent(),
            'required' => $this->isRequired(),
            'messages' => $this->getMessages(),
            'readonly' => $this->isReadOnly(),
            'disabled' => $this->isDisabled(),
            'index' => $this->getIndex(),
            'mask' => $this->getMask(),
            'actions' => $this->getRenderedActions($model),
            'attributes' => array_filter([
                'id' => $this->getId(),
                'type' => $this->getType(),
                'name' => $this->getName(),
                'placeholder' => $this->getPlaceholder(),
                'required' => $this->isRequired(),
                'readonly' => $this->isReadOnly(),
                'disabled' => $this->isDisabled(),
            ]),
        ], $this->getGridLayoutConfig());
    }
}
