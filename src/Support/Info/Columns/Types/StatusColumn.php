<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class StatusColumn extends Column
{
    protected string $type = 'status';

    protected ?string $component = 'info-column-status';

    public function __construct($name, $label = null)
    {
        parent::__construct($name, $label);

        $this->icon('CheckCircle');
    }

    protected array $statusMap = [
        'active' => ['label' => 'Ativo', 'color' => 'success'],
        'inactive' => ['label' => 'Inativo', 'color' => 'muted'],
        'published' => ['label' => 'Publicado', 'color' => 'success'],
        'draft' => ['label' => 'Rascunho', 'color' => 'warning'],
        'pending' => ['label' => 'Pendente', 'color' => 'warning'],
        'canceled' => ['label' => 'Cancelado', 'color' => 'destructive'],
        'suspended' => ['label' => 'Suspenso', 'color' => 'destructive'],
    ];

    /**
     * Define mapeamento customizado de status
     */
    public function statusMap(array $map): self
    {
        $this->statusMap = array_merge($this->statusMap, $map);

        return $this;
    }

    public function render(mixed $value, $row = null): array
    {
        $columnName = $this->getName();
        $statusKey = strtolower($value ?? '');

        // Busca no mapa de status
        $statusInfo = $this->statusMap[$statusKey] ?? [
            'label' => ucfirst($value),
            'color' => 'muted',
        ];

        // Usa formatador da coluna se existir
        if ($this->castCallback) {
            $formatted = $this->evaluate($this->castCallback, ['value' => $value, 'column' => $this]);
        } else {
            $formatted = $statusInfo['label'];
        }

        return [
            'text' => (string) $formatted,
            'icon' => $this->getIcon(),
            'tooltip' => $this->getTooltip(),
            'type' => $this->getType(),
            'component' => $this->getComponent(),
            'color' => $statusInfo['color'],
        ];
    }
}
