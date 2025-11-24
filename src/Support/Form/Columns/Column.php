<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns\HasGridLayout;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToHelpers;
use Closure;

abstract class Column extends AbstractColumn
{
    use BelongsToHelpers;
    use HasGridLayout;

    protected string $type = 'text';

    protected ?string $component = 'form-field-text';

    protected Closure|null $valueUsing = null;

    public function __construct($name, $label = null)
    {
        $this->name($name);
        $this->id($name);
        $this->label($label ?? ucfirst($name));
        $this->columnSpanFull();

        $this->valueUsing(function ($request, $model) {
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
            'attributes' => array_filter([
                'id' => $this->getId(),
                'type' => $this->getType(),
                'name' => $this->getName(),
                'placeholder' => $this->getPlaceholder(),
                'required' => $this->isRequired(),
            ]),
        ], $this->getGridLayoutConfig());
    }
}
