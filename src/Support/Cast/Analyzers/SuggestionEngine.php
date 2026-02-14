<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\Analyzers;

use Illuminate\Database\Eloquent\Model;

/**
 * SuggestionEngine - Sugere formatters, casts e validações
 */
class SuggestionEngine
{
    /**
     * Sugere formatters para colunas de um modelo
     */
    public static function suggestFormatters(Model $model, array $schema): array
    {
        $suggestions = [];

        foreach ($schema['columns'] ?? [] as $columnName => $columnInfo) {
            $formatter = static::suggestFormatterForColumn($columnInfo);
            if ($formatter) {
                $suggestions[$columnName] = $formatter;
            }
        }

        return $suggestions;
    }

    /**
     * Sugere formatter para uma coluna específica
     */
    protected static function suggestFormatterForColumn(array $columnInfo): ?array
    {
        $type = $columnInfo['type'] ?? null;
        $category = $columnInfo['category'] ?? null;
        $name = $columnInfo['name'] ?? '';

        // Baseado na categoria detectada
        $suggestion = match ($category) {
            'datetime', 'date' => [
                'formatter' => 'DateFormatter::relative()',
                'reason' => 'Column type is '.$type,
            ],
            'time' => [
                'formatter' => 'DateFormatter::time()',
                'reason' => 'Column type is time',
            ],
            'boolean' => [
                'formatter' => 'CastFormatter::boolean()',
                'reason' => 'Column type is boolean',
            ],
            'money' => [
                'formatter' => 'MoneyFormatter::brl()',
                'reason' => 'Column type suggests monetary value',
            ],
            'json' => [
                'formatter' => 'CastFormatter::json()',
                'reason' => 'Column type is JSON',
            ],
            'integer', 'float' => [
                'formatter' => 'NumberFormatter::decimal()',
                'reason' => 'Column type is numeric',
            ],
            default => null,
        };

        // Ajustes baseados no nome do campo
        if (str_contains($name, 'price') || str_contains($name, 'cost') || str_contains($name, 'salary')) {
            $suggestion = [
                'formatter' => 'MoneyFormatter::brl()',
                'reason' => 'Field name suggests monetary value',
            ];
        } elseif (str_contains($name, 'percent') || str_contains($name, 'rate')) {
            $suggestion = [
                'formatter' => 'NumberFormatter::percentage()',
                'reason' => 'Field name suggests percentage',
            ];
        } elseif (str_contains($name, 'size') || str_contains($name, 'bytes')) {
            $suggestion = [
                'formatter' => 'NumberFormatter::filesize()',
                'reason' => 'Field name suggests file size',
            ];
        }

        return $suggestion;
    }

    /**
     * Sugere casts do Eloquent para colunas
     */
    public static function suggestCasts(Model $model, array $schema): array
    {
        $suggestions = [];
        $currentCasts = $model->getCasts();

        foreach ($schema['columns'] ?? [] as $columnName => $columnInfo) {
            // Pula se já tem cast definido
            if (isset($currentCasts[$columnName])) {
                continue;
            }

            $suggestedCast = static::suggestCastForColumn($columnInfo);
            if ($suggestedCast) {
                $suggestions[$columnName] = $suggestedCast;
            }
        }

        return $suggestions;
    }

    /**
     * Sugere cast para uma coluna
     */
    protected static function suggestCastForColumn(array $columnInfo): ?string
    {
        $category = $columnInfo['category'] ?? null;

        return match ($category) {
            'datetime' => 'datetime',
            'date' => 'date',
            'boolean' => 'boolean',
            'json' => 'array',
            'integer' => 'integer',
            'float', 'money' => 'decimal:2',
            default => null,
        };
    }

    /**
     * Sugere tipo de input HTML para um campo
     */
    public static function suggestInputType(string $fieldName, ?array $columnInfo = null): string
    {
        // Baseado no nome do campo
        if (str_contains($fieldName, 'email')) {
            return 'email';
        }
        if (str_contains($fieldName, 'password')) {
            return 'password';
        }
        if (str_contains($fieldName, 'url') || str_contains($fieldName, 'website')) {
            return 'url';
        }
        if (str_contains($fieldName, 'phone') || str_contains($fieldName, 'tel')) {
            return 'tel';
        }
        if (str_contains($fieldName, 'date') && ! str_contains($fieldName, 'time')) {
            return 'date';
        }
        if (str_contains($fieldName, 'time') && ! str_contains($fieldName, 'date')) {
            return 'time';
        }
        if (str_contains($fieldName, 'datetime') || str_contains($fieldName, 'timestamp')) {
            return 'datetime-local';
        }
        if (str_contains($fieldName, 'color')) {
            return 'color';
        }

        // Baseado no tipo da coluna
        if ($columnInfo) {
            $category = $columnInfo['category'] ?? null;

            return match ($category) {
                'boolean' => 'checkbox',
                'date' => 'date',
                'datetime' => 'datetime-local',
                'time' => 'time',
                'integer', 'float', 'money' => 'number',
                'text', 'longtext' => 'textarea',
                'json' => 'textarea',
                default => 'text',
            };
        }

        return 'text';
    }

    /**
     * Sugere regras de validação para um campo
     */
    public static function suggestValidation(string $fieldName, ?array $columnInfo = null): array
    {
        $rules = [];

        if ($columnInfo) {
            // Nullable
            if (! ($columnInfo['nullable'] ?? true)) {
                $rules[] = 'required';
            }

            // Tipo
            $category = $columnInfo['category'] ?? null;
            match ($category) {
                'integer' => $rules[] = 'integer',
                'float', 'money' => $rules[] = 'numeric',
                'boolean' => $rules[] = 'boolean',
                'date', 'datetime' => $rules[] = 'date',
                'json' => $rules[] = 'array',
                default => null,
            };

            // Tamanho máximo
            if (isset($columnInfo['length']) && $columnInfo['length'] > 0) {
                $rules[] = 'max:'.$columnInfo['length'];
            }

            // Unique para campos de index único
            if (($columnInfo['key'] ?? '') === 'UNI') {
                $rules[] = 'unique:table,'.$fieldName;
            }
        }

        // Baseado no nome do campo
        if (str_contains($fieldName, 'email')) {
            $rules[] = 'email';
        }
        if (str_contains($fieldName, 'url') || str_contains($fieldName, 'website')) {
            $rules[] = 'url';
        }

        return $rules;
    }

    /**
     * Analisa campos fillable e sugere melhorias
     */
    public static function analyzeFillable(Model $model, array $schema): array
    {
        $fillable = $model->getFillable();
        $columns = array_keys($schema['columns'] ?? []);

        // Colunas que existem mas não estão em fillable
        $missingFromFillable = array_diff($columns, $fillable);

        // Remove colunas de sistema
        $systemColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $missingFromFillable = array_diff($missingFromFillable, $systemColumns);

        return [
            'fillable' => $fillable,
            'missing_from_fillable' => array_values($missingFromFillable),
            'system_columns' => array_intersect($columns, $systemColumns),
            'fillable_count' => count($fillable),
            'suggestion' => count($missingFromFillable) > 0
                ? 'Consider adding these columns to $fillable: '.implode(', ', $missingFromFillable)
                : 'All non-system columns are fillable',
        ];
    }
}
