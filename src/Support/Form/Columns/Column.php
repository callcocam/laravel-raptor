<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToHelpers;

abstract class Column extends AbstractColumn
{
    use BelongsToHelpers;

    protected string $type = 'text';

    protected ?string $component = 'form-column-text';

    public function __construct($name, $label = null)
    {
        $this->name($name);
        $this->id($name);
        $this->label($label ?? ucfirst($name));
    }

    public function toArray(): array
    {
        return [
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
            'attributes' => array_filter([
                'id' => $this->getId(),
                'type' => $this->getType(),
                'name' => $this->getName(),
                'placeholder' => $this->getPlaceholder(),
            ]),
        ];
    }
}
