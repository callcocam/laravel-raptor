<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Table\Columns\Column;

class BooleanColumn extends Column
{
    protected ?string $component = 'table-column-boolean';

    protected ?string $trueLabel = null;

    protected ?string $falseLabel = null;

    protected ?string $trueColor = 'success';

    protected ?string $falseColor = 'destructive';

    protected ?string $trueIcon = 'Check';

    protected ?string $falseIcon = 'X';

    public function trueLabel(string $label): self
    {
        $this->trueLabel = $label;

        return $this;
    }

    public function falseLabel(string $label): self
    {
        $this->falseLabel = $label;

        return $this;
    }

    public function trueColor(string $color): self
    {
        $this->trueColor = $color;

        return $this;
    }

    public function falseColor(string $color): self
    {
        $this->falseColor = $color;

        return $this;
    }

    public function trueIcon(string $icon): self
    {
        $this->trueIcon = $icon;

        return $this;
    }

    public function falseIcon(string $icon): self
    {
        $this->falseIcon = $icon;

        return $this;
    }

    public function render(mixed $value, $row = null): mixed
    {
        return (bool) $value;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'trueLabel' => $this->trueLabel ?? 'Sim',
            'falseLabel' => $this->falseLabel ?? 'Não',
            'trueColor' => $this->trueColor,
            'falseColor' => $this->falseColor,
            'trueIcon' => $this->trueIcon,
            'falseIcon' => $this->falseIcon,
        ]);
    }
}
