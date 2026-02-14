<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Support\Import\Columns\Column;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Support\Import\Contracts\AfterPersistHookInterface;
use Callcocam\LaravelRaptor\Support\Import\Contracts\BeforePersistHookInterface;
use Callcocam\LaravelRaptor\Support\Import\Contracts\GeneratesImportId;
use Callcocam\LaravelRaptor\Support\Import\Contracts\ImportServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /** @var array<int, array{row: int, data: array<string, mixed>, message: string}> */
    protected array $failedRowsData = [];

    /** @var array<int, array{row: int, data: array<string, mixed>}> Linhas persistidas com sucesso (para afterProcess). */
    protected array $completedRows = [];

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

            $existing = $this->findExistingByKeys($data);

            if ($existing === null && $this->sheet->shouldGenerateId()) {
                $data['id'] = $this->generateId($data);
            }

            $this->validate($data, $rowNumber, $existing);

            $beforeClass = $this->sheet->getBeforePersistClass();
            if ($beforeClass && class_exists($beforeClass)) {
                $hook = app($beforeClass);
                if ($hook instanceof BeforePersistHookInterface) {
                    $data = $hook->beforePersist($data, $rowNumber, $existing);
                    if ($data === null) {
                        return;
                    }
                }
            }

            $model = null;
            $connection = $this->getEffectiveConnection();
            if ($connection) {
                DB::connection($connection)->transaction(function () use ($data, $existing, &$model): void {
                    $model = $this->persist($data, $existing);
                });
            } else {
                DB::transaction(function () use ($data, $existing, &$model): void {
                    $model = $this->persist($data, $existing);
                });
            }

            $dataForCompleted = $data;
            if ($model instanceof Model && ! isset($dataForCompleted['id'])) {
                $dataForCompleted['id'] = $model->getKey();
            }
            $this->completedRows[] = ['row' => $rowNumber, 'data' => $dataForCompleted];

            $afterClass = $this->sheet->getAfterPersistClass();
            if ($afterClass && class_exists($afterClass) && $model instanceof Model) {
                $hook = app($afterClass);
                if ($hook instanceof AfterPersistHookInterface) {
                    $hook->afterPersist($model, $data, $rowNumber);
                }
            }

            $this->successfulRows++;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->failedRows++;
            $allMessages = [];
            foreach ($e->errors() as $attribute => $messages) {
                foreach ($messages as $message) {
                    $this->errors[] = ['row' => $rowNumber, 'message' => $message, 'column' => $attribute];
                    $allMessages[] = ($attribute ? "{$attribute}: " : '').$message;
                }
            }
            $this->failedRowsData[] = [
                'row' => $rowNumber,
                'data' => $row,
                'message' => implode('; ', $allMessages),
            ];
        } catch (\Throwable $e) {
            $this->failedRows++;
            $this->errors[] = ['row' => $rowNumber, 'message' => $e->getMessage()];
            $this->failedRowsData[] = [
                'row' => $rowNumber,
                'data' => $row,
                'message' => $e->getMessage(),
            ];
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
                $value = $this->normalizeExcelErrorValue($value);
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
     * Converte valores de erro do Excel (#N/A, #DIV/0!, etc.) em null para não persistir literalmente.
     */
    protected function normalizeExcelErrorValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $err = strtoupper(trim($value));
        if (in_array($err, ['#N/A', '#DIV/0!', '#VALUE!', '#REF!', '#NAME?', '#NUM!', '#NULL!', '#GETTING_DATA'], true)) {
            return null;
        }

        return $value;
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
     * Busca registro existente pelas chaves de updateBy (quando configurado).
     *
     * @param  array<string, mixed>  $data
     */
    protected function findExistingByKeys(array $data): ?\Illuminate\Database\Eloquent\Model
    {
        $updateByKeys = $this->sheet->getUpdateByKeys();
        if ($updateByKeys === null || $updateByKeys === []) {
            return null;
        }

        $modelClass = $this->sheet->getModelClass();
        if (! $modelClass || ! class_exists($modelClass)) {
            return null;
        }

        $model = app($modelClass);
        if ($this->connection ?? $this->sheet->getConnection()) {
            $model->setConnection($this->connection ?? $this->sheet->getConnection());
        }

        $query = $model->newQuery();
        foreach ($updateByKeys as $key) {
            if (array_key_exists($key, $data)) {
                $query->where($key, $data[$key]);
            }
        }

        $found = $query->first();

        return $found instanceof \Illuminate\Database\Eloquent\Model ? $found : null;
    }

    /**
     * Valida os dados com as regras das colunas.
     * Quando há registro existente (updateBy), regras unique são ajustadas para ignorar o id desse registro.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validate(array $data, int $rowNumber, ?\Illuminate\Database\Eloquent\Model $existing = null): void
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

            $columnRules = is_array($columnRules) ? $columnRules : explode('|', (string) $columnRules);

            if ($existing !== null) {
                $columnRules = $this->applyUniqueIgnoreToRules($columnRules, $name, $existing);
            }

            $rules[$name] = $columnRules;

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
     * Ajusta regras 'unique' para ignorar o id do registro existente (updateBy).
     *
     * @param  array<int, string>  $rules
     * @return array<int, string>
     */
    protected function applyUniqueIgnoreToRules(array $rules, string $attribute, \Illuminate\Database\Eloquent\Model $existing): array
    {
        $id = $existing->getKey();
        $table = $existing->getTable();

        return array_map(function ($rule) use ($attribute, $id, $table) {
            if (is_string($rule) && str_starts_with($rule, 'unique')) {
                if (preg_match('#^unique:([^,]+),([^,]+)#', $rule, $m)) {
                    return sprintf('unique:%s,%s,%s', $m[1], $m[2], $id);
                }

                return sprintf('unique:%s,%s,%s', $table, $attribute, $id);
            }

            return $rule;
        }, $rules);
    }

    /**
     * Persiste os dados na tabela da Sheet (Model ou query builder).
     * Remove colunas marcadas com excludeFromSave antes de salvar.
     * Retorna o Model quando usa Eloquent; null quando usa DB::table().
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data, ?Model $existing = null): ?Model
    {
        $dataForSave = $this->filterDataForPersist($data);

        $connection = $this->getEffectiveConnection();
        $tableName = $this->sheet->getTableName(); 

        if ($tableName === null) {
            return null;
        }

        $modelClass = $this->sheet->getModelClass();

        if ($modelClass && class_exists($modelClass)) {
            $model = app($modelClass);
            if ($connection) {
                $model->setConnection($connection);
            }

            if ($existing !== null) {
                unset($dataForSave['id'], $dataForSave['created_at']);
                $existing->forceFill($dataForSave)->save();

                return $existing;
            }

            $instance = $model->newInstance();
            $instance->forceFill($dataForSave);
            $instance->save();

            return $instance;
        }

        if ($connection) {
            DB::connection($connection)->table($tableName)->insert($this->prepareDataForInsert($dataForSave));
        } else {
            DB::table($tableName)->insert($this->prepareDataForInsert($dataForSave));
        }

        return null;
    }

    protected function getEffectiveConnection(): ?string
    {
        return $this->connection ?? $this->sheet->getConnection();
    }

    /**
     * Remove do array as colunas marcadas como excludeFromSave (usadas só em regras/validação).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function filterDataForPersist(array $data): array
    {
        $excludedKeys = $this->sheet->getColumnNamesExcludedFromSave();
        if ($excludedKeys === []) {
            return $data;
        }

        return array_diff_key($data, array_flip($excludedKeys));
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

    /**
     * @return array<int, array{row: int, data: array<string, mixed>, message: string}>
     */
    public function getFailedRowsData(): array
    {
        return $this->failedRowsData;
    }

    /**
     * Linhas persistidas com sucesso (row + data com id e campos exclude) para o hook afterProcess.
     *
     * @return array<int, array{row: int, data: array<string, mixed>}>
     */
    public function getCompletedRows(): array
    {
        return $this->completedRows;
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
