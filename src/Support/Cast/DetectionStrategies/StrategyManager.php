<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

/**
 * StrategyManager - Gerencia e executa estratégias de detecção
 */
class StrategyManager
{
    /**
     * @var DetectionStrategy[]
     */
    protected array $strategies = [];

    /**
     * Registra estratégias padrão
     */
    public function __construct()
    {
        $this->registerDefaultStrategies();
    }

    /**
     * Registra as estratégias padrão do sistema
     */
    protected function registerDefaultStrategies(): void
    {
        $this->register(new BooleanDetectionStrategy());
        $this->register(new DateDetectionStrategy());
        $this->register(new PercentageDetectionStrategy());
        $this->register(new MoneyDetectionStrategy());
        $this->register(new FilesizeDetectionStrategy());
        $this->register(new JsonDetectionStrategy());
        $this->register(new NumericDetectionStrategy()); // Baixa prioridade, genérico
    }

    /**
     * Registra uma estratégia
     */
    public function register(DetectionStrategy $strategy): void
    {
        $this->strategies[] = $strategy;

        // Ordena por prioridade (maior primeiro)
        usort($this->strategies, fn ($a, $b) => $b->getPriority() <=> $a->getPriority());
    }

    /**
     * Remove todas as estratégias
     */
    public function clearStrategies(): void
    {
        $this->strategies = [];
    }

    /**
     * Detecta e retorna o formatter apropriado
     */
    public function detect(mixed $value): ?object
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->matches($value)) {
                return $strategy->getFormatter($value);
            }
        }

        return null;
    }

    /**
     * Retorna todas as estratégias registradas
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Debug: mostra quais estratégias detectam um valor
     */
    public function debugMatches(mixed $value): array
    {
        $matches = [];

        foreach ($this->strategies as $strategy) {
            $matches[get_class($strategy)] = [
                'matches' => $strategy->matches($value),
                'priority' => $strategy->getPriority(),
            ];
        }

        return $matches;
    }
}
