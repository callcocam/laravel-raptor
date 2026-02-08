<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Support\Import\Columns\Column;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Support\Import\Contracts\GeneratesImportId;
use Callcocam\LaravelRaptor\Support\Import\Contracts\ImportServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Service padrão de importação para uma Sheet.
 *
 * Uma Sheet = uma tabela. relatedSheets são abas com colunas da mesma tabela (lookupKey).
 * Responsável por: aplicar defaults (colunas hidden), gerar ID (GeneratesImportId), validar e persistir.
 */
class DefaultImportService implements ImportServiceInterface
{
    protected int $successfulRows = 0;

    protected int $failedRows = 0;

    /** @var array<int, array{row: int, message: string, column?: string}> */
    protected array $errors = [];

    /** @var array<string, mixed> */
    protected array $context = [];

    public function __construct(
        protected Sheet $sheet,
        protected ?string $connection = null,
        ?array $context = null
    ) {
        if ($context !== null) {
            $this->context = $context;
        }
    }

    /**
     * Processa uma linha: ignora vazias, contexto (hidden), gerar ID, mapear/processValue, validar, persistir.
     */
    public function processRow(array $row, int $rowNumber): void
    {
        if ($this->isEmptyRow($row)) {
            return;
        }

        try {
            $data = $this->buildDataFromRow($row);

            if ($this->sheet->shouldGenerateId()) {
                $data['id'] = $this->generateId($data);
            }

            $this->validate($data, $rowNumber);

            $this->persist($data);

            $this->successfulRows++;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->failedRows++;
            foreach ($e->errors() as $attribute => $messages) {
                foreach ($messages as $message) {
                    $this->errors[] = ['row' => $rowNumber, 'message' => $message, 'column' => $attribute];
                }
            }
        } catch (\Throwable $e) {
            $this->failedRows++;
            $this->errors[] = ['row' => $rowNumber, 'message' => $e->getMessage()];
        }
    }

    /**
     * Monta o array de dados a partir da linha: colunas hidden do context, demais do row + processValue.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function buildDataFromRow(array $row): array
    {
        $data = [];

        foreach ($this->sheet->getColumns() as $column) {
            if (! $column instanceof Column || ! method_exists($column, 'processValue')) {
                continue;
            }

            $name = $column->getName();
            $label = (string) $column->getLabel();

            if ($column->isHidden()) {
                $value = $this->context[$name] ?? $column->getDefaultValue();
            } else {
                $value = $row[$label] ?? $row[$name] ?? null;
            }

            $data[$name] = $column->processValue($value, $row);
        }

        return $data;
    }

    /**
     * Verifica se a linha do Excel está vazia (todos os valores null ou string vazia).
     *
     * @param  array<string, mixed>  $row
     */
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Gera o ID usando a classe configurada na Sheet (GeneratesImportId).
     *
     * @param  array<string, mixed>  $data
     */
    protected function generateId(array $data): string
    {
        $class = $this->sheet->getGenerateIdClass();

        if (! $class || ! class_exists($class)) {
            return (string) \Illuminate\Support\Str::ulid();
        }

        $generator = app($class);

        if (! $generator instanceof GeneratesImportId) {
            return (string) \Illuminate\Support\Str::ulid();
        }

        return $generator->generate($data);
    }

    /**
     * Valida os dados com as regras das colunas.
     * Para update/upsert futuro: regra unique pode precisar ignorar o próprio registro (ex.: unique:products,ean,{id}).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validate(array $data, int $rowNumber): void
    {
        $rules = [];
        $messages = [];

        foreach ($this->sheet->getColumns() as $column) {
            if (! $column instanceof Column || ! method_exists($column, 'getRules')) {
                continue;
            }

            $name = $column->getName();
            $columnRules = $column->getRules(null);

            if ($columnRules === null || $columnRules === []) {
                continue;
            }

            $rules[$name] = is_array($columnRules) ? $columnRules : explode('|', (string) $columnRules);

            if (method_exists($column, 'getMessages') && $column->getMessages()) {
                foreach ($column->getMessages() as $key => $message) {
                    $messages["{$name}.{$key}"] = $message;
                }
            }
        }

        if ($rules === []) {
            return;
        }

        Validator::make($data, $rules, $messages)->validate();
    }

    /**
     * Persiste os dados na tabela da Sheet (Model ou query builder).
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        $connection = $this->connection ?? $this->sheet->getConnection();
        $tableName = $this->sheet->getTableName();

        if ($tableName === null) {
            return;
        }

        $modelClass = $this->sheet->getModelClass();

        if ($modelClass && class_exists($modelClass)) {
            $model = app($modelClass);
            if ($connection) {
                $model->setConnection($connection);
            }
            // forceFill permite definir id mesmo quando o model tem $guarded = ['id'] (import com gerador de ID)
            $instance = $model->newInstance();
            $instance->forceFill($data);
            $instance->save();

            return;
        }

        $query = DB::connection($connection)->table($tableName);
        $query->insert($this->prepareDataForInsert($data));
    }

    /**
     * Prepara dados para insert (converte DateTime etc. para string).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareDataForInsert(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        return $data;
    }

    public function getSuccessfulRows(): int
    {
        return $this->successfulRows;
    }

    public function getFailedRows(): int
    {
        return $this->failedRows;
    }

    /**
     * @return array<int, array{row: int, message: string, column?: string}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setContext(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    public function getSheet(): Sheet
    {
        return $this->sheet;
    }
}
