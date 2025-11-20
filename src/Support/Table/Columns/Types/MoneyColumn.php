<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Table\Columns\Column;

class MoneyColumn extends Column
{
    protected ?string $component = "table-column-text";
    protected string $currency = 'BRL';
    protected string $locale = 'pt_BR';
    protected int $decimals = 2;

    public function currency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function locale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function decimals(int $decimals): self
    {
        $this->decimals = $decimals;
        return $this;
    }

    public function render(mixed $value, $row = null): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = floatval($value);

        $formatter = new \NumberFormatter($this->locale, \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($value, $this->currency);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'currency' => $this->currency,
            'locale' => $this->locale,
            'decimals' => $this->decimals,
        ]);
    }
}
