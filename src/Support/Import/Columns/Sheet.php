<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Columns;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns;
use Callcocam\LaravelRaptor\Support\Import\Contracts\GeneratesImportId;
use Callcocam\LaravelRaptor\Support\Import\Generators\DefaultUlidGenerator;

class Sheet extends AbstractColumn
{
    use Concerns\Interacts\WithColumns;

    protected ?string $serviceClass = null;

    protected ?string $modelClass = null;

    protected ?string $tableName = null;

    protected ?string $database = null;

    protected ?string $connection = null;

    protected array $relatedSheets = [];

    protected ?Sheet $parentSheet = null;

    protected ?string $lookupKey = null;

    protected bool $generateId = false;

    protected $generateIdCallback = null;

    protected ?string $generateIdClass = null;

    public function __construct(string $name)
    {
        $this->name($name);
        $this->type('sheet');
        $this->setUp();
    }

    /**
     * Inclui todas as colunas (inclusive hidden) no payload para o Job.
     */
    public function getArrayColumns($model = null): array
    {
        $result = [];
        foreach ($this->getColumns() as $column) {
            $result[] = method_exists($column, 'toArray') ? $column->toArray($model) : [];
        }

        return $result;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'columns' => $this->getArrayColumns(),
            'serviceClass' => $this->getServiceClass(),
            'modelClass' => $this->getModelClass(),
            'tableName' => $this->getTableName(),
            'database' => $this->getDatabase(),
            'connection' => $this->getConnection(),
            'relatedSheets' => array_map(fn ($sheet) => $sheet->toArray(), $this->relatedSheets),
            'lookupKey' => $this->getLookupKey(),
            'generateId' => $this->shouldGenerateId(),
            'generateIdClass' => $this->getGenerateIdClass(),
            'type' => $this->getType(),
        ];
    }

    public function serviceClass(?string $class): self
    {
        $this->serviceClass = $class;

        return $this;
    }

    public function getServiceClass(): ?string
    {
        return $this->serviceClass;
    }

    public function modelClass(?string $class): self
    {
        $this->modelClass = $class;

        return $this;
    }

    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }

    /**
     * Define o nome da tabela diretamente (sem precisar de Model)
     */
    public function table(string $tableName, ?string $database = null): self
    {
        $this->tableName = $tableName;

        if ($database) {
            $this->database = $database;
        }

        return $this;
    }

    public function getTableName(): ?string
    {
        // Se tem tableName, usa ele
        if ($this->tableName) {
            return $this->tableName;
        }

        // Se tem modelClass, pega a tabela do model
        if ($this->modelClass) {
            return app($this->modelClass)->getTable();
        }

        return null;
    }

    /**
     * Define o nome do database (opcional)
     */
    public function database(?string $database): self
    {
        $this->database = $database;

        return $this;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    /**
     * Define a conexão do banco de dados
     */
    public function connection(?string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function getConnection(): ?string
    {
        // Se tem connection explícita, usa ela
        if ($this->connection) {
            return $this->connection;
        }

        // Se tem modelClass, pega a conexão do model
        if ($this->modelClass) {
            return app($this->modelClass)->getConnectionName();
        }

        // Usa a conexão padrão
        return null;
    }

    /**
     * Adiciona uma sheet relacionada que será mesclada com a principal
     * usando uma chave de busca (lookup key)
     *
     * @param  string  $sheetName  Nome da sheet relacionada
     * @param  string  $lookupKey  Campo usado para relacionar os dados (ex: 'ean', 'code')
     * @return self Retorna a própria sheet (fluent interface)
     */
    public function addSheet(string $sheetName, string $lookupKey = 'id'): self
    {
        $relatedSheet = new self($sheetName);
        $relatedSheet->parentSheet = $this;
        $relatedSheet->lookupKey = $lookupKey;

        // Herda configurações da sheet pai
        if ($this->modelClass) {
            $relatedSheet->modelClass($this->modelClass);
        }

        if ($this->tableName) {
            $relatedSheet->table($this->tableName, $this->database);
        }

        if ($this->connection) {
            $relatedSheet->connection($this->connection);
        }

        // Herda as colunas da sheet pai (as relacionadas usam as mesmas definições)
        $relatedSheet->columns($this->getColumns());

        $this->relatedSheets[] = $relatedSheet;

        return $this;
    }

    /**
     * Retorna todas as sheets relacionadas
     */
    public function getRelatedSheets(): array
    {
        return $this->relatedSheets;
    }

    /**
     * Verifica se esta sheet possui sheets relacionadas
     */
    public function hasRelatedSheets(): bool
    {
        return ! empty($this->relatedSheets);
    }

    /**
     * Retorna a sheet pai (se for uma sheet relacionada)
     */
    public function getParentSheet(): ?Sheet
    {
        return $this->parentSheet;
    }

    /**
     * Verifica se esta é uma sheet relacionada
     */
    public function isRelatedSheet(): bool
    {
        return $this->parentSheet !== null;
    }

    /**
     * Retorna a chave de busca para relacionar sheets
     */
    public function getLookupKey(): ?string
    {
        return $this->lookupKey;
    }

    /**
     * Define a chave de busca
     */
    public function lookupKey(string $key): self
    {
        $this->lookupKey = $key;

        return $this;
    }

    /**
     * Habilita geração de ID com callback opcional.
     * Se não informar classe nem callback, usa DefaultUlidGenerator.
     */
    public function generateId(?callable $callback = null): self
    {
        $this->generateId = true;
        $this->generateIdCallback = $callback;

        if (! $this->generateIdClass && $callback === null) {
            $this->generateIdClass = DefaultUlidGenerator::class;
        }

        return $this;
    }

    /**
     * Define a classe que gera o ID para esta sheet.
     * A classe deve implementar GeneratesImportId (ex: ProductUlid, CategoryUlid).
     * Cada sheet pode ter gerador diferente (products vs categories, etc.).
     *
     * @param  class-string<GeneratesImportId>  $generatorClass
     */
    public function generateIdUsing(string $generatorClass): self
    {
        $this->generateId = true;
        $this->generateIdClass = $generatorClass;

        return $this;
    }

    public function shouldGenerateId(): bool
    {
        return $this->generateId;
    }

    public function getGenerateIdCallback(): ?callable
    {
        return $this->generateIdCallback;
    }

    public function getGenerateIdClass(): ?string
    {
        return $this->generateIdClass;
    }

    public function isSheet(): bool
    {
        return true;
    }
}
