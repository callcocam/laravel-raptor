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
    protected ?string $defaultIcon = 'CheckCircleIcon';
    protected bool $editable = false;
    protected ?string $executeUrl = null;
    protected ?string $statusKey = null; 
    protected array $activeValues = ['active', 'published', '1', 'true', 'ativo']; 

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

    public function editable(bool $editable = true): self
    {
        $this->editable = $editable;
        return $this;
    }

    public function executeUrl(string $url): self
    {
        $this->executeUrl = $url;
        return $this;
    }

    public function statusKey(string $key): self
    {
        $this->statusKey = $key;
        return $this;
    } 

    public function activeValues(array $values): self
    {
        $this->activeValues = $values;
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
            'editable' => $this->editable,
            'executeUrl' => $this->executeUrl,
            'statusKey' => $this->statusKey ?? $this->name,
            'hasCallback' => $this->callback !== null,
            'activeValues' => $this->activeValues,
        ]);
    }
}
