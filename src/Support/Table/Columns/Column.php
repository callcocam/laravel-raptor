<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns;

use Callcocam\LaravelRaptor\Support\AbstractColumn;

abstract class Column extends AbstractColumn
{

    protected ?string $component = "text-column";

    public function __construct(string $name, ?string $label = null)
    {
        $this->name($name);
        $this->label($label ?? ucwords(str_replace('_', ' ', $name)));
        $this->setUp();
    }

    abstract public function render(mixed $value, array $row): mixed;
}
