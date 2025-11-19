<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\DetectionStrategies;

/**
 * DetectionStrategy - Interface para estratégias de detecção de tipos
 */
interface DetectionStrategy
{
    /**
     * Verifica se o valor corresponde ao tipo da estratégia
     */
    public function matches(mixed $value): bool;

    /**
     * Retorna o formatter apropriado para o valor
     */
    public function getFormatter(mixed $value): ?object;

    /**
     * Retorna a prioridade da estratégia (maior = mais prioritária)
     */
    public function getPriority(): int;
}
