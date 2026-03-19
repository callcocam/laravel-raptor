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
use Closure;
use Illuminate\Database\Eloquent\Builder;

abstract class Column extends AbstractColumn
{
    use BelongsToHelpers;
    use HasGridLayout;
    use HasMak;
    use WithActions;

    protected string $type = 'text';

    protected ?string $component = 'form-field-text';

    protected ?Closure $valueUsing = null;

    protected ?Closure $defaultUsing = null;

    protected Closure|string|Builder|\Illuminate\Database\Query\Builder|null $queryUsing = null;

    protected Closure|string|null $executeUrl = null;

    protected Closure|string|null $executeMethod = null;

    protected Closure|string|null $executeParams = null;

    protected Closure|bool|null $reload = null;

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

    public function executeUrl(Closure|string|null $executeUrl): static
    {
        $this->executeUrl = $executeUrl;

        return $this;
    }

    public function executeMethod(Closure|string|null $executeMethod): static
    {
        $this->executeMethod = $executeMethod;

        return $this;
    }

    public function executeParams(Closure|string|null $executeParams): static
    {
        $this->executeParams = $executeParams;

        return $this;
    }
    public function getExecuteUrl(): ?string
    {
        return $this->evaluate($this->executeUrl);
    }

    public function getExecuteMethod(): ?string
    {
        return $this->evaluate($this->executeMethod);
    }

    public function getExecuteParams(): ?string
    {
        return $this->evaluate($this->executeParams);
    }

    public function reload(Closure|bool|null $reload = true): static
    {
        $this->reload = $reload;

        return $this;
    }

    public function getReload(): ?bool
    {
        return $this->evaluate($this->reload);
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
        return ! is_null($this->defaultUsing);
    }

    public function queryUsing(Closure|string|Builder|\Illuminate\Database\Query\Builder|null $queryUsing): self
    {
        $this->queryUsing = $queryUsing;

        return $this;
    }

    public function getQueryUsing(): Closure|string|Builder|\Illuminate\Database\Query\Builder|null
    {
        if (is_string($this->queryUsing)) {
            return app($this->queryUsing);
        }
        if ($this->queryUsing instanceof Builder) {
            return $this->queryUsing;
        }
        if ($this->queryUsing instanceof \Illuminate\Database\Query\Builder) {
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
        if ($model instanceof \Illuminate\Database\Eloquent\Model) {
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
            'executeUrl' => $this->getExecuteUrl(),
            'executeMethod' => $this->getExecuteMethod(),
            'executeParams' => $this->getExecuteParams(),
            'reload' => $this->getReload(),
        ], $this->getGridLayoutConfig());
    }
}
