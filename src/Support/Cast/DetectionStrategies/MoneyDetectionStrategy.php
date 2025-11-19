<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

use Callcocam\LaravelRaptor\Support\Cast\Formatters\MoneyFormatter;

/**
 * MoneyDetectionStrategy - Detecta valores monetários
 */
class MoneyDetectionStrategy extends AbstractDetectionStrategy
{
    protected int $priority = 60;

    protected array $patterns = [
        'brl' => '/^R\$\s?[\d.,]+$/',
        'usd' => '/^\$[\d.,]+$/',
        'eur' => '/^€[\d.,]+$/',
    ];

    public function matches(mixed $value): bool
    {
        // Strings formatadas como dinheiro
        if (is_string($value)) {
            foreach ($this->patterns as $pattern) {
                if ($this->matchesPattern($value, $pattern)) {
                    return true;
                }
            }
        }

        // Números que parecem dinheiro
        if (is_numeric($value)) {
            return $this->looksLikeMoney((float) $value);
        }

        return false;
    }

    public function getFormatter(mixed $value): ?object
    {
        // Detecta moeda pela string
        if (is_string($value)) {
            if ($this->matchesPattern($value, $this->patterns['usd'])) {
                return MoneyFormatter::usd();
            }
            if ($this->matchesPattern($value, $this->patterns['eur'])) {
                return MoneyFormatter::eur();
            }
        }

        // Padrão: BRL
        return MoneyFormatter::brl();
    }

    /**
     * Verifica se número parece dinheiro
     * - Tem exatamente 2 casas decimais
     * - Está em range razoável
     */
    protected function looksLikeMoney(float $value): bool
    {
        return $value > 0 &&
               $value < 1000000 &&
               $this->hasExactDecimals($value, 2);
    }
}
