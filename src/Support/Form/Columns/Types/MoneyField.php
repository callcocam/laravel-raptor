<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class MoneyField extends Column
{
    protected string $currency = 'BRL';
    
    protected string $locale = 'pt_BR';
    
    protected int $decimals = 2;
    
    protected string $decimalSeparator = ',';
    
    protected string $thousandsSeparator = '.';

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->type('text');
        $this->component('form-field-money');
        $this->setUp();

        $this->valueUsing(function ($data, $model) {
            $currentValue = data_get($data, $this->getName(), null);

            if (is_null($currentValue) || $currentValue === '') {
                return null;
            }

            // Se já for numérico, retorna
            if (is_numeric($currentValue)) {
                return (float) $currentValue;
            }

            // Remove separadores de milhares e substitui separador decimal
            $normalized = str_replace($this->thousandsSeparator, '', $currentValue);
            $normalized = str_replace($this->decimalSeparator, '.', $normalized);
            
            // Remove qualquer caractere que não seja número, ponto ou sinal negativo
            $normalized = preg_replace('/[^\d.\-]/', '', $normalized);

            return is_numeric($normalized) ? (float) $normalized : null;
        });

        $this->defaultUsing(function ($data, $model) {
            $currentValue = data_get($model, $this->getName(), null);

            if (is_null($currentValue)) {
                return null;
            }

            // Converte para float e formata de acordo com a configuração
            return number_format(
                (float) $currentValue, 
                $this->decimals, 
                $this->decimalSeparator, 
                $this->thousandsSeparator
            );
        });
    }

    /**
     * Define a moeda (BRL, USD, EUR, etc.)
     */
    public function currency(string $currency): static
    {
        $this->currency = strtoupper($currency);
        
        // Configurações padrão para moedas comuns
        match($this->currency) {
            'BRL' => $this->configureBRL(),
            'USD' => $this->configureUSD(),
            'EUR' => $this->configureEUR(),
            default => null,
        };
        
        return $this;
    }

    /**
     * Define o locale (pt_BR, en_US, etc.)
     */
    public function locale(string $locale): static
    {
        $this->locale = $locale;
        
        return $this;
    }

    /**
     * Define o número de casas decimais
     */
    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;
        
        return $this;
    }

    /**
     * Define o separador decimal
     */
    public function decimalSeparator(string $separator): static
    {
        $this->decimalSeparator = $separator;
        
        return $this;
    }

    /**
     * Define o separador de milhares
     */
    public function thousandsSeparator(string $separator): static
    {
        $this->thousandsSeparator = $separator;
        
        return $this;
    }

    /**
     * Configuração para Real Brasileiro (BRL)
     */
    protected function configureBRL(): static
    {
        $this->locale = 'pt_BR';
        $this->decimalSeparator = ',';
        $this->thousandsSeparator = '.';
        $this->decimals = 2;
        
        return $this;
    }

    /**
     * Configuração para Dólar Americano (USD)
     */
    protected function configureUSD(): static
    {
        $this->locale = 'en_US';
        $this->decimalSeparator = '.';
        $this->thousandsSeparator = ',';
        $this->decimals = 2;
        
        return $this;
    }

    /**
     * Configuração para Euro (EUR)
     */
    protected function configureEUR(): static
    {
        $this->locale = 'de_DE';
        $this->decimalSeparator = ',';
        $this->thousandsSeparator = '.';
        $this->decimals = 2;
        
        return $this;
    }

    /**
     * Retorna a moeda
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Retorna o locale
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Retorna o número de decimais
     */
    public function getDecimals(): int
    {
        return $this->decimals;
    }

    /**
     * Retorna o separador decimal
     */
    public function getDecimalSeparator(): string
    {
        return $this->decimalSeparator;
    }

    /**
     * Retorna o separador de milhares
     */
    public function getThousandsSeparator(): string
    {
        return $this->thousandsSeparator;
    }

    /**
     * Adiciona as configurações de moeda ao array
     */
    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'currency' => $this->getCurrency(),
            'locale' => $this->getLocale(),
            'decimals' => $this->getDecimals(),
            'decimalSeparator' => $this->getDecimalSeparator(),
            'thousandsSeparator' => $this->getThousandsSeparator(),
        ]);
    }
}
