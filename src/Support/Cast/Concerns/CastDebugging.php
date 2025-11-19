<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\Concerns;

/**
 * CastDebugging - Métodos utilitários para debug e benchmark do sistema de casts
 */
trait CastDebugging
{
    /**
     * Obtém estatísticas do registry/detector
     */
    public static function getStats(): array
    {
        $stats = [
            'total_entries' => 0,
            'cache_size' => 0,
            'cache_enabled' => false,
        ];

        // Se a classe que usa o trait tem esses arrays, inclui nas stats
        if (isset(static::$fieldCasts)) {
            $stats['field_casts_count'] = count(static::$fieldCasts);
            $stats['total_entries'] += count(static::$fieldCasts);
        }

        if (isset(static::$casts)) {
            $stats['type_casts_count'] = count(static::$casts);
            $stats['total_entries'] += array_sum(array_map('count', static::$casts));
        }

        if (isset(static::$cache)) {
            $stats['cache_size'] = count(static::$cache);
        }

        if (isset(static::$config['cache_enabled'])) {
            $stats['cache_enabled'] = static::$config['cache_enabled'];
        }

        if (isset(static::$patterns)) {
            $stats['patterns_count'] = count(static::$patterns);
        }

        if (isset(static::$fieldHints)) {
            $stats['field_hints_count'] = count(static::$fieldHints);
        }

        return $stats;
    }

    /**
     * Testa performance do sistema de cast
     */
    public static function benchmark(array $testData, int $iterations = 100): array
    {
        $start = microtime(true);
        $memory_start = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testData as $item) {
                // Chama o método de detecção/resolução da classe que usa o trait
                if (method_exists(static::class, 'resolve')) {
                    static::resolve($item['value'], $item['field'] ?? null);
                } elseif (method_exists(static::class, 'detect')) {
                    static::detect($item['value'], $item['field'] ?? null);
                }
            }
        }

        $end = microtime(true);
        $memory_end = memory_get_usage();

        $total_time = $end - $start;
        $total_items = $iterations * count($testData);

        return [
            'iterations' => $iterations,
            'items_per_iteration' => count($testData),
            'total_items' => $total_items,
            'total_time' => round($total_time, 4),
            'average_time' => round($total_time / $total_items, 6),
            'items_per_second' => round($total_items / $total_time, 2),
            'memory_used' => $memory_end - $memory_start,
            'cache_hits' => isset(static::$cache) ? count(static::$cache) : 0,
        ];
    }

    /**
     * Obtém informações de debug sobre uma detecção
     */
    public static function debug(mixed $value, ?string $fieldName = null): array
    {
        $debug = [
            'value' => $value,
            'value_type' => gettype($value),
            'field_name' => $fieldName,
            'timestamp' => time(),
        ];

        // Detecta o formatter se o método existir
        if (method_exists(static::class, 'detect')) {
            $formatter = static::detect($value, $fieldName);
            $debug['detected_formatter'] = $formatter ? get_class($formatter) : 'none';
        } elseif (method_exists(static::class, 'resolve')) {
            $formatter = static::resolve($value, $fieldName);
            $debug['detected_formatter'] = $formatter ? get_class($formatter) : 'none';
        }

        // Detecta categoria do campo se o método existir
        if (method_exists(static::class, 'detectFieldCategory')) {
            $debug['field_category'] = static::detectFieldCategory($fieldName);
        }

        // Análises de tipo do valor
        if (method_exists(static::class, 'isDateValue')) {
            $debug['is_date'] = static::isDateValue($value);
        }

        $debug['is_numeric'] = is_numeric($value);
        $debug['is_array'] = is_array($value);
        $debug['is_object'] = is_object($value);
        $debug['is_bool'] = is_bool($value);

        // Análise de string patterns
        if (is_string($value)) {
            if (method_exists(static::class, 'isJsonString')) {
                $debug['is_json'] = static::isJsonString($value);
            }

            if (method_exists(static::class, 'getMatchedPatterns')) {
                $debug['string_patterns'] = static::getMatchedPatterns($value);
            }
        }

        return $debug;
    }

    /**
     * Debug de múltiplos valores (batch)
     */
    public static function debugBatch(array $items): array
    {
        $results = [];
        $summary = [
            'total_items' => count($items),
            'formatters_used' => [],
            'categories_found' => [],
        ];

        foreach ($items as $key => $item) {
            $value = is_array($item) ? ($item['value'] ?? null) : $item;
            $field = is_array($item) ? ($item['field'] ?? null) : null;

            $debug = static::debug($value, $field);
            $results[$key] = $debug;

            // Contabiliza formatters
            if (isset($debug['detected_formatter'])) {
                $formatter = $debug['detected_formatter'];
                $summary['formatters_used'][$formatter] = ($summary['formatters_used'][$formatter] ?? 0) + 1;
            }

            // Contabiliza categorias
            if (isset($debug['field_category'])) {
                $category = $debug['field_category'];
                $summary['categories_found'][$category] = ($summary['categories_found'][$category] ?? 0) + 1;
            }
        }

        return [
            'items' => $results,
            'summary' => $summary,
        ];
    }

    /**
     * Analisa eficiência do cache
     */
    public static function analyzeCacheEfficiency(): array
    {
        if (! isset(static::$cache)) {
            return ['error' => 'No cache available in this class'];
        }

        $cacheSize = count(static::$cache);
        $maxSize = static::$config['max_cache_size'] ?? 1000;
        $utilizationPercent = ($cacheSize / $maxSize) * 100;

        return [
            'current_size' => $cacheSize,
            'max_size' => $maxSize,
            'utilization_percent' => round($utilizationPercent, 2),
            'cache_enabled' => static::$config['cache_enabled'] ?? false,
            'recommendation' => static::getCacheRecommendation($utilizationPercent),
        ];
    }

    /**
     * Recomendação baseada na utilização do cache
     */
    protected static function getCacheRecommendation(float $utilization): string
    {
        return match (true) {
            $utilization >= 90 => 'Cache quase cheio - considere aumentar max_cache_size',
            $utilization >= 70 => 'Boa utilização do cache',
            $utilization >= 40 => 'Utilização moderada do cache',
            $utilization >= 10 => 'Baixa utilização - cache pode estar sendo limpo frequentemente',
            default => 'Cache praticamente vazio - verifique se está habilitado',
        };
    }

    /**
     * Exporta configuração atual para debugging
     */
    public static function exportConfig(): array
    {
        $export = [
            'class' => static::class,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if (isset(static::$config)) {
            $export['config'] = static::$config;
        }

        if (isset(static::$patterns)) {
            $export['patterns'] = array_keys(static::$patterns);
            $export['patterns_count'] = count(static::$patterns);
        }

        if (isset(static::$fieldHints)) {
            $export['field_hints'] = static::$fieldHints;
            $export['field_hints_categories'] = array_keys(static::$fieldHints);
        }

        if (isset(static::$fieldCasts)) {
            $export['registered_field_casts'] = array_keys(static::$fieldCasts);
        }

        if (isset(static::$casts)) {
            $export['registered_type_casts'] = array_keys(static::$casts);
        }

        return $export;
    }
}
