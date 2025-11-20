<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Info;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToHelpers;

abstract class Column extends AbstractColumn
{
    use BelongsToHelpers;

    protected string $type = 'text';

    protected ?string $component = 'info-column-text';

    public function __construct($name, $label = null)
    {
        $this->name($name);
        $this->id($name);
        $this->label($label ?? ucfirst($name));
    }
    
    abstract public function render(mixed $value, $row = null): mixed;

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'component' => $this->getComponent(),
            'visible' => $this->isVisible(),
            'tooltip' => $this->getTooltip(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'prefix' => $this->getPrefix(),
            'suffix' => $this->getSuffix(),
            'default' => $this->getDefault(),
            'helpText' => $this->getHelpText(),
            'hint' => $this->getHint(),
        ];
    }
}
