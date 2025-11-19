<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Table\Concerns\HasSearchable;
use Callcocam\LaravelRaptor\Support\Table\Concerns\HasSortable;
use Illuminate\Database\Eloquent\Model;

abstract class Column extends AbstractColumn
{

    use HasSearchable;
    use HasSortable;
    
    protected ?string $component = "table-column-text";

    public function __construct(string $name, ?string $label = null)
    {
        $this->name($name);
        $this->label($label ?? ucwords(str_replace('_', ' ', $name)));
        $this->setUp();
    }

    abstract public function render(mixed $value,  $row = null): mixed;

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'component' => $this->getComponent(),
            'searchable' => $this->isSearchable(),
            'sortable' => $this->isSortable(),
            'visible' => $this->isVisible(),
            'tooltip' => $this->getTooltip(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
        ];
    }
}
