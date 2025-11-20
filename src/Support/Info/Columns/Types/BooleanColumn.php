<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class BooleanColumn extends Column
{
    protected string $type = 'boolean';

    protected ?string $component = 'info-column-boolean';

    protected string $trueLabel = 'Sim';

    protected string $falseLabel = 'Não';

    protected string $trueIcon = 'CheckCircle';

    protected string $falseIcon = 'XCircle';

    /**
     * Define labels customizados
     */
    public function labels(string $trueLabel, string $falseLabel): self
    {
        $this->trueLabel = $trueLabel;
        $this->falseLabel = $falseLabel;

        return $this;
    }

    /**
     * Define ícones customizados
     */
    public function icons(string $trueIcon, string $falseIcon): self
    {
        $this->trueIcon = $trueIcon;
        $this->falseIcon = $falseIcon;

        return $this;
    }

    public function render($value, $row = null): array
    {
        $columnName = $this->getName();

        // Converte o valor para booleano
        $boolValue = $this->toBool($value);

        // Usa formatador da coluna se existir
        if ($this->castCallback) {
            $formatted = $this->evaluate($this->castCallback, ['value' => $value, 'column' => $this]);
        } else {
            $formatted = $boolValue ? $this->trueLabel : $this->falseLabel;
        }

        return [
            'text' => (string) $formatted,
            'icon' => $this->getIcon() ?: ($boolValue ? $this->trueIcon : $this->falseIcon),
            'tooltip' => $this->getTooltip(),
            'type' => $this->getType(),
            'component' => $this->getComponent(),
            'value' => $boolValue,
            'color' => $boolValue ? 'success' : 'muted',
        ];
    }

    /**
     * Converte valor para booleano
     */
    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_null($value)) {
            return false;
        }

        if (is_numeric($value)) {
            return $value > 0;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));

            return in_array($value, ['1', 'true', 'yes', 'sim', 'on'], true);
        }

        return (bool) $value;
    }
}
