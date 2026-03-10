<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Table\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class StatusColumn extends Column
{
    protected ?string $component = 'table-column-status';

    protected array $statusConfig = [];

    protected ?string $defaultColor = 'secondary';

    protected ?string $defaultIcon = 'CheckCircleIcon';

    public function __construct(?string $name = 'status', ?string $label = 'Status')
    {
        parent::__construct($name, $label);

        $this
            ->editable()
            ->statusKey('status')
            ->columnSpanTwo()
            ->statuses([
                'draft' => [
                    'label' => 'Rascunho',
                    'color' => 'muted',
                    'icon' => 'FileText',
                ],
                'published' => [
                    'label' => 'Publicado',
                    'color' => 'success',
                    'icon' => 'CheckCircle',
                ],
            ])
            ->callback(function (Request $request, Model $model) {
                $statusKey = $request->input('fieldName', 'status');
                $newValue = $request->input($statusKey);
                $model->update([
                    $statusKey => $newValue == 'inactive' ? 'draft' : 'published',
                ]);

                return [
                    'notification' => [
                        'title' => 'Status Atualizado',
                        'text' => 'O status foi atualizado com sucesso.',
                        'type' => 'success',
                    ],
                ];
            });
    }

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
        return $this->getFormattedValue($value, $row);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'statusConfig' => $this->statusConfig,
            'defaultColor' => $this->defaultColor,
            'defaultIcon' => $this->defaultIcon,
            'hasCallback' => $this->callback !== null,
        ]);
    }
}
