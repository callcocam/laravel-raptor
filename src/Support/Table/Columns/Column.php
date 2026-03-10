<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Actions\Concerns\HasActionCallback;
use Callcocam\LaravelRaptor\Support\Concerns\HasGridLayout;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithColumns;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToHelpers;
use Callcocam\LaravelRaptor\Support\Table\Columns\Concerns\HasEditable;
use Callcocam\LaravelRaptor\Support\Table\Concerns\HasSearchable;
use Callcocam\LaravelRaptor\Support\Table\Concerns\HasSortable;
use Closure;

abstract class Column extends AbstractColumn
{
    use BelongsToHelpers;
    use HasActionCallback;
    use HasEditable;
    use HasGridLayout;
    use HasSearchable;
    use HasSortable;
    use WithColumns;

    protected ?Closure $formatter = null;

    protected ?string $component = 'table-column-text';

    protected bool $primary = false;

    public function __construct(string $name, ?string $label = null)
    {
        $this->name($name);
        $this->label($label ?? ucwords(str_replace('_', ' ', $name)));
        $this->columnSpanSix();
        $this->setUp();
    }

    abstract public function render(mixed $value, $row = null): mixed;

    public function primary(bool $value = true): static
    {
        $this->primary = $value;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }


    public function formatter(Closure $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function getFormatter(): ?Closure
    {
        return $this->formatter;
    }

    public function getFormattedValue(mixed $value, $row = null): mixed
    {
        if ($this->getFormatter()) {
            return $this->evaluate($this->getFormatter(), [
                'model' => $row,
                'value' => $value,
                'row' => $row,
            ]);
        }

        return $value;
    }

    public function toArray(): array
    {
        return array_merge([
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
            'prefix' => $this->getPrefix(),
            'suffix' => $this->getSuffix(),
            'primary' => $this->isPrimary(),
            'columns' => $this->getArrayColumns(),
        ], $this->getEditableToArray(), $this->getGridLayoutConfig());
    }
}
