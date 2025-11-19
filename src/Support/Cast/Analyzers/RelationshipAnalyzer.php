<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\Analyzers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * RelationshipAnalyzer - Analisa relacionamentos do Eloquent
 */
class RelationshipAnalyzer
{
    /**
     * Analisa todos os relacionamentos de um modelo
     */
    public static function analyze(Model $model): array
    {
        $relationships = [];
        $reflection = new \ReflectionClass($model);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== get_class($model) || $method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                $return = $method->invoke($model);

                if ($return instanceof Relation) {
                    $relationships[$method->name] = static::analyzeRelation($return, $method->name);
                }
            } catch (\Exception $e) {
                // Ignora métodos que não são relacionamentos
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Analisa detalhes de um relacionamento específico
     */
    protected static function analyzeRelation(Relation $relation, string $name): array
    {
        $type = class_basename($relation);

        return [
            'name' => $name,
            'type' => $type,
            'related_model' => get_class($relation->getRelated()),
            'foreign_key' => static::getForeignKey($relation, $type),
            'local_key' => static::getLocalKey($relation, $type),
            'table' => $relation->getRelated()->getTable(),
        ];
    }

    /**
     * Obtém a foreign key de um relacionamento
     */
    protected static function getForeignKey(Relation $relation, string $type): ?string
    {
        try {
            return match ($type) {
                'BelongsTo' => $relation->getForeignKeyName(),
                'HasOne', 'HasMany', 'MorphOne', 'MorphMany' => $relation->getForeignKeyName(),
                'BelongsToMany', 'MorphToMany' => $relation->getForeignPivotKeyName(),
                default => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtém a local key de um relacionamento
     */
    protected static function getLocalKey(Relation $relation, string $type): ?string
    {
        try {
            return match ($type) {
                'BelongsTo' => $relation->getOwnerKeyName(),
                'HasOne', 'HasMany' => $relation->getLocalKeyName(),
                'BelongsToMany' => $relation->getRelatedPivotKeyName(),
                default => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Conta relacionamentos por tipo
     */
    public static function countByType(array $relationships): array
    {
        $counts = [];

        foreach ($relationships as $relationship) {
            $type = $relationship['type'];
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Identifica relacionamentos não utilizados (sem eager loading)
     */
    public static function findUnusedRelationships(Model $model): array
    {
        $relationships = static::analyze($model);
        $eagerLoad = method_exists($model, 'getWith') ? $model->getWith() : [];

        $unused = [];
        foreach (array_keys($relationships) as $relationName) {
            if (! in_array($relationName, $eagerLoad)) {
                $unused[] = $relationName;
            }
        }

        return $unused;
    }
}
