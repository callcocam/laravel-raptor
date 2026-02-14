<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast;

use Callcocam\LaravelRaptor\Support\Cast\Analyzers\RelationshipAnalyzer;
use Callcocam\LaravelRaptor\Support\Cast\Analyzers\SchemaAnalyzer;
use Callcocam\LaravelRaptor\Support\Cast\Analyzers\SuggestionEngine;
use Illuminate\Database\Eloquent\Model;

/**
 * ModelIntrospection - Coordena análises de modelos usando Analyzers
 * Versão simplificada que delega para analyzers especializados
 */
class ModelIntrospectionSimplified
{
    protected static array $analysisCache = [];

    /**
     * Analisa completamente um modelo
     */
    public static function analyze(string|Model $model): array
    {
        $modelClass = is_string($model) ? $model : get_class($model);

        if (isset(static::$analysisCache[$modelClass])) {
            return static::$analysisCache[$modelClass];
        }

        $analysis = static::performAnalysis($modelClass);
        static::$analysisCache[$modelClass] = $analysis;

        return $analysis;
    }

    /**
     * Realiza análise completa usando os Analyzers
     */
    protected static function performAnalysis(string $modelClass): array
    {
        if (! static::canAnalyze($modelClass)) {
            return ['error' => 'Invalid model class'];
        }

        try {
            $model = new $modelClass;

            // Usa os Analyzers especializados
            $schema = SchemaAnalyzer::analyzeTable($model);
            $relationships = RelationshipAnalyzer::analyze($model);

            return [
                'model_info' => static::getModelInfo($model),
                'table_schema' => $schema,
                'relationships' => $relationships,
                'relationship_counts' => RelationshipAnalyzer::countByType($relationships),
                'suggested_formatters' => SuggestionEngine::suggestFormatters($model, $schema),
                'suggested_casts' => SuggestionEngine::suggestCasts($model, $schema),
                'fillable_analysis' => SuggestionEngine::analyzeFillable($model, $schema),
                'performance_hints' => static::getPerformanceHints($model, $relationships),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'model_class' => $modelClass,
            ];
        }
    }

    /**
     * Obtém informações básicas do modelo
     */
    protected static function getModelInfo(Model $model): array
    {
        $reflection = new \ReflectionClass($model);

        return [
            'class_name' => get_class($model),
            'table_name' => $model->getTable(),
            'primary_key' => $model->getKeyName(),
            'timestamps' => $model->timestamps,
            'soft_deletes' => method_exists($model, 'trashed'),
            'fillable' => $model->getFillable(),
            'guarded' => $model->getGuarded(),
            'casts' => $model->getCasts(),
            'file_path' => $reflection->getFileName(),
        ];
    }

    /**
     * Dicas de performance
     */
    protected static function getPerformanceHints(Model $model, array $relationships): array
    {
        $hints = [];

        // Relacionamentos sem eager loading
        $unused = RelationshipAnalyzer::findUnusedRelationships($model);
        if (count($unused) > 0) {
            $hints[] = [
                'type' => 'N+1 Query Prevention',
                'severity' => 'medium',
                'message' => 'Consider adding eager loading for: '.implode(', ', $unused),
                'suggestion' => 'protected $with = [\''.implode('\', \'', array_slice($unused, 0, 3)).'\'];',
            ];
        }

        // Timestamps desnecessários
        if ($model->timestamps && count($model->getFillable()) === 0) {
            $hints[] = [
                'type' => 'Timestamps',
                'severity' => 'low',
                'message' => 'Timestamps enabled but no fillable fields',
                'suggestion' => 'Consider disabling: public $timestamps = false;',
            ];
        }

        // Muitos relacionamentos
        if (count($relationships) > 10) {
            $hints[] = [
                'type' => 'Model Complexity',
                'severity' => 'medium',
                'message' => 'Model has '.count($relationships).' relationships',
                'suggestion' => 'Consider splitting into smaller models or using traits',
            ];
        }

        return $hints;
    }

    /**
     * Gera relatório resumido
     */
    public static function generateReport(string|Model $model): array
    {
        $analysis = static::analyze($model);

        return [
            'summary' => static::generateSummary($analysis),
            'recommendations' => static::generateRecommendations($analysis),
            'full_analysis' => $analysis,
        ];
    }

    /**
     * Gera sumário da análise
     */
    protected static function generateSummary(array $analysis): array
    {
        return [
            'model' => $analysis['model_info']['class_name'] ?? 'Unknown',
            'table' => $analysis['model_info']['table_name'] ?? 'Unknown',
            'columns_count' => count($analysis['table_schema']['columns'] ?? []),
            'relationships_count' => count($analysis['relationships'] ?? []),
            'fillable_fields' => count($analysis['model_info']['fillable'] ?? []),
            'suggested_formatters' => count($analysis['suggested_formatters'] ?? []),
            'suggested_casts' => count($analysis['suggested_casts'] ?? []),
            'performance_issues' => count($analysis['performance_hints'] ?? []),
        ];
    }

    /**
     * Gera recomendações
     */
    protected static function generateRecommendations(array $analysis): array
    {
        $recommendations = [];

        // Casts sugeridos
        if (! empty($analysis['suggested_casts'])) {
            $recommendations[] = [
                'category' => 'Eloquent Casts',
                'priority' => 'high',
                'description' => 'Add these casts to improve type safety',
                'code_example' => static::generateCastExample($analysis['suggested_casts']),
            ];
        }

        // Performance hints
        foreach ($analysis['performance_hints'] ?? [] as $hint) {
            $recommendations[] = [
                'category' => $hint['type'],
                'priority' => $hint['severity'],
                'description' => $hint['message'],
                'code_example' => $hint['suggestion'] ?? null,
            ];
        }

        return $recommendations;
    }

    /**
     * Gera exemplo de código para casts
     */
    protected static function generateCastExample(array $casts): string
    {
        $code = "protected \$casts = [\n";
        foreach ($casts as $field => $cast) {
            $code .= "    '{$field}' => '{$cast}',\n";
        }
        $code .= '];';

        return $code;
    }

    /**
     * Utilitários
     */
    public static function tableExists(string $tableName): bool
    {
        return SchemaAnalyzer::tableExists($tableName);
    }

    public static function getAllTables(): array
    {
        return SchemaAnalyzer::getAllTables();
    }

    public static function clearCache(): void
    {
        static::$analysisCache = [];
        SchemaAnalyzer::clearCache();
    }

    public static function canAnalyze(string $modelClass): bool
    {
        return class_exists($modelClass) && is_subclass_of($modelClass, Model::class);
    }
}
