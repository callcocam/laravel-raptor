<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Cast\Analyzers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * SchemaAnalyzer - Analisa schema de tabelas e colunas
 */
class SchemaAnalyzer
{
    protected static array $schemaCache = [];

    /**
     * Tipos de coluna mapeados para categorias
     */
    protected static array $columnTypeMapping = [
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'date' => 'date',
        'time' => 'time',
        'boolean' => 'boolean',
        'tinyint' => 'boolean',
        'decimal' => 'money',
        'numeric' => 'money',
        'money' => 'money',
        'json' => 'json',
        'text' => 'text',
        'longtext' => 'text',
        'mediumtext' => 'text',
        'integer' => 'integer',
        'int' => 'integer',
        'bigint' => 'integer',
        'smallint' => 'integer',
        'float' => 'float',
        'double' => 'float',
        'varchar' => 'string',
        'char' => 'string',
        'string' => 'string',
    ];

    /**
     * Analisa schema completo de uma tabela
     */
    public static function analyzeTable(Model $model): array
    {
        $tableName = $model->getTable();

        if (isset(static::$schemaCache[$tableName])) {
            return static::$schemaCache[$tableName];
        }

        try {
            $columns = Schema::getColumnListing($tableName);
            $columnDetails = [];

            foreach ($columns as $column) {
                $columnDetails[$column] = static::getColumnDetails($tableName, $column);
            }

            $schema = [
                'table_name' => $tableName,
                'columns' => $columnDetails,
                'indexes' => static::getIndexes($tableName),
                'foreign_keys' => static::getForeignKeys($tableName),
            ];

            static::$schemaCache[$tableName] = $schema;

            return $schema;
        } catch (\Exception $e) {
            return [
                'error' => 'Could not retrieve schema: '.$e->getMessage(),
                'table_name' => $tableName,
            ];
        }
    }

    /**
     * Obtém detalhes de uma coluna
     */
    public static function getColumnDetails(string $tableName, string $columnName): array
    {
        try {
            if (! Schema::hasColumn($tableName, $columnName)) {
                return ['error' => 'Column does not exist'];
            }

            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();

            $columnInfo = $connection->selectOne('
                SELECT
                    COLUMN_NAME,
                    DATA_TYPE,
                    CHARACTER_MAXIMUM_LENGTH,
                    NUMERIC_PRECISION,
                    NUMERIC_SCALE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT,
                    COLUMN_KEY,
                    EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            ', [$database, $tableName, $columnName]);

            if (! $columnInfo) {
                return ['name' => $columnName, 'type' => 'unknown'];
            }

            return [
                'name' => $columnInfo->COLUMN_NAME,
                'type' => $columnInfo->DATA_TYPE,
                'length' => $columnInfo->CHARACTER_MAXIMUM_LENGTH,
                'precision' => $columnInfo->NUMERIC_PRECISION,
                'scale' => $columnInfo->NUMERIC_SCALE,
                'nullable' => $columnInfo->IS_NULLABLE === 'YES',
                'default' => $columnInfo->COLUMN_DEFAULT,
                'key' => $columnInfo->COLUMN_KEY,
                'extra' => $columnInfo->EXTRA,
                'category' => static::$columnTypeMapping[$columnInfo->DATA_TYPE] ?? 'string',
            ];
        } catch (\Exception $e) {
            return [
                'name' => $columnName,
                'type' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtém indexes de uma tabela
     */
    public static function getIndexes(string $tableName): array
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();

            $indexes = $connection->select('
                SELECT
                    INDEX_NAME,
                    COLUMN_NAME,
                    NON_UNIQUE,
                    SEQ_IN_INDEX
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY INDEX_NAME, SEQ_IN_INDEX
            ', [$database, $tableName]);

            $groupedIndexes = [];
            foreach ($indexes as $index) {
                $indexName = $index->INDEX_NAME;
                if (! isset($groupedIndexes[$indexName])) {
                    $groupedIndexes[$indexName] = [
                        'name' => $indexName,
                        'unique' => $index->NON_UNIQUE == 0,
                        'columns' => [],
                    ];
                }
                $groupedIndexes[$indexName]['columns'][] = $index->COLUMN_NAME;
            }

            return array_values($groupedIndexes);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtém foreign keys de uma tabela
     */
    public static function getForeignKeys(string $tableName): array
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();

            $foreignKeys = $connection->select('
                SELECT
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ', [$database, $tableName]);

            return array_map(fn ($fk) => [
                'constraint' => $fk->CONSTRAINT_NAME,
                'column' => $fk->COLUMN_NAME,
                'references_table' => $fk->REFERENCED_TABLE_NAME,
                'references_column' => $fk->REFERENCED_COLUMN_NAME,
            ], $foreignKeys);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Verifica se tabela existe
     */
    public static function tableExists(string $tableName): bool
    {
        try {
            return Schema::hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Lista todas as tabelas
     */
    public static function getAllTables(): array
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();

            $tables = $connection->select("
                SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
            ", [$database]);

            return array_map(fn ($table) => $table->TABLE_NAME, $tables);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Limpa cache de schemas
     */
    public static function clearCache(): void
    {
        static::$schemaCache = [];
    }
}
