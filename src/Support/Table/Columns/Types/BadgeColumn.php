<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Table\Columns\Column;

class BadgeColumn extends Column
{
    protected ?string $component = "table-column-text";
    protected array $colorMap = [];
    protected ?string $defaultColor = 'secondary';

    public function colors(array $colorMap): self
    {
        $this->colorMap = $colorMap;
        return $this;
    }

    public function color(string $value, string $color): self
    {
        $this->colorMap[$value] = $color;
        return $this;
    }

    public function defaultColor(string $color): self
    {
        $this->defaultColor = $color;
        return $this;
    }

    public function render(mixed $value, $row = null): mixed
    {
        return $value;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'colorMap' => $this->colorMap,
            'defaultColor' => $this->defaultColor,
        ]);
    }
}
