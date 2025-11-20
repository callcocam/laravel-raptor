<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Table\Columns\Column;

class StatusColumn extends Column
{
    protected ?string $component = "table-column-status";
    protected array $statusConfig = [];
    protected ?string $defaultColor = 'secondary';
    protected ?string $defaultIcon = null;

    public function status(string $value, string $label, ?string $color = null, ?string $icon = null): self
    {
        $this->statusConfig[$value] = [
            'label' => $label,
            'color' => $color ?? $this->defaultColor,
            'icon' => $icon ?? $this->defaultIcon,
        ];
        return $this;
    }

    public function statuses(array $statuses): self
    {
        foreach ($statuses as $value => $config) {
            if (is_string($config)) {
                $this->status($value, $config);
            } else {
                $this->status(
                    $value,
                    $config['label'] ?? $value,
                    $config['color'] ?? null,
                    $config['icon'] ?? null
                );
            }
        }
        return $this;
    }

    public function defaultColor(string $color): self
    {
        $this->defaultColor = $color;
        return $this;
    }

    public function defaultIcon(string $icon): self
    {
        $this->defaultIcon = $icon;
        return $this;
    }

    public function render(mixed $value, $row = null): mixed
    {
        return $value;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'statusConfig' => $this->statusConfig,
            'defaultColor' => $this->defaultColor,
            'defaultIcon' => $this->defaultIcon,
        ]);
    }
}
