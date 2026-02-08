<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Support\Import\Contracts\GeneratesImportId;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DefaultImportService
{
    protected int $successfulRows = 0;

    protected int $failedRows = 0;

    protected array $errors = [];

    protected array $relatedData = [];

    public function __construct(
        protected Sheet $sheet,
        protected ?string $connection = null
    ) {}

    /**
     * Define os dados relacionados de outras sheets
     * Esses dados serão usados para merge com a sheet principal
     */
    public function setRelatedData(array $relatedData): void
    {
        $this->relatedData = $relatedData;
    }

    /**
     * Processa uma linha da importação
     */
    public function processRow(array $row, int $rowNumber): bool
    {
        try {
            // Mapeia os dados da linha para as colunas definidas
            $data = $this->mapRowData($row);

            // Se esta sheet tem uma lookupKey, armazena os dados para merge posterior
            if ($this->sheet->isRelatedSheet() && $lookupKey = $this->sheet->getLookupKey()) {
                if (isset($data[$lookupKey])) {
                    $this->storeRelatedData($data[$lookupKey], $data);
                }

                $this->successfulRows++;

                return true;
            }

            // Mescla com dados relacionados se houver
            $data = $this->mergeRelatedData($data);

            // Valida os dados
            if (! $this->validateData($data, $rowNumber)) {
                $this->failedRows++;

                return false;
            }

            // Salva no banco de dados
            $this->saveData($data, $row);

            $this->successfulRows++;

            return true;

        } catch (\Exception $e) {
            $this->failedRows++;
            $this->errors[] = [
                'row' => $rowNumber,
                'message' => $e->getMessage(),
            ];

            report($e);

            return false;
        }
    }

    /**
     * Mapeia os dados da linha para o formato esperado
     */
    protected function mapRowData(array $row): array
    {
        $data = [];
        $columns = $this->sheet->getColumns();

        foreach ($columns as $column) {
            $name = $column->getName();
            $label = $column->getLabel();
            $index = $column->getIndex();

            $columnSheet = $column->getSheetName();

            if ($columnSheet && $columnSheet !== $this->sheet->getName()) {
                continue;
            }

            if (! $columnSheet && $this->sheet->isRelatedSheet()) {
                $lookupKey = $this->sheet->getLookupKey();

                if (! $lookupKey || $name !== $lookupKey) {
                    continue;
                }
            }

            if ($column->isHidden()) {
                $data[$name] = $column->processValue(null, $row);

                continue;
            }

            // Determina qual chave usar para buscar o valor
            $key = $index ?? $label ?? $name;

            // Pega o valor da linha
            $value = $this->getValueFromRow($row, $key);

            // Processa o valor através da coluna (aplica render, format, cast)
            $data[$name] = $column->processValue($value, $row);
        }

        return $data;
    }

    /**
     * Obtém o valor da linha pela chave (pode ser índice numérico ou string)
     */
    protected function getValueFromRow(array $row, string|int $key): mixed
    {
        // Se a chave existe diretamente
        if (array_key_exists($key, $row)) {
            return $row[$key];
        }

        // Se for string, tenta buscar case-insensitive
        if (is_string($key)) {
            $keyLower = strtolower($key);

            foreach ($row as $rowKey => $value) {
                if (strtolower((string) $rowKey) === $keyLower) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Valida os dados conforme as regras definidas
     */
    protected function validateData(array $data, int $rowNumber): bool
    {
        $rules = [];
        $messages = [];
        $columns = $this->sheet->getColumns();

        foreach ($columns as $column) {
            $name = $column->getName();
            $columnRules = $column->getRules();

            if (! empty($columnRules)) {
                $rules[$name] = $columnRules;
            }

            $columnMessages = $column->getMessages();
            if (! empty($columnMessages)) {
                foreach ($columnMessages as $messageKey => $messageValue) {
                    $messages["{$name}.{$messageKey}"] = $messageValue;
                }
            }
        }

        if (empty($rules)) {
            return true;
        }

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $this->errors[] = [
                'row' => $rowNumber,
                'errors' => $validator->errors()->toArray(),
            ];

            return false;
        }

        return true;
    }

    /**
     * Salva os dados no banco
     */
    protected function saveData(array $data, array $row): void
    {
        $tableName = $this->sheet->getTableName();
        $connection = $this->connection ?? $this->sheet->getConnection();

        if (! $tableName) {
            throw new \Exception('Nome da tabela não definido para a sheet: '.$this->sheet->getName());
        }

        // Se tem modelClass, usa o model para salvar
        if ($modelClass = $this->sheet->getModelClass()) {
            $model = app($modelClass);

            if ($connection) {
                $model->setConnection($connection);
            }

            // Tenta usar updateOrCreate se houver chaves únicas
            $uniqueKeys = $this->getUniqueKeys($data);

            $existingRecord = null;

            if (! empty($uniqueKeys)) {
                $existingRecord = $model::on($connection)->where($uniqueKeys)->first();
            }

            if ($this->shouldGenerateId($data) && ! $existingRecord) {
                $data['id'] = $this->generateId($row);
            }

            if (! empty($uniqueKeys)) {
                $model::on($connection)->updateOrCreate($uniqueKeys, $data);
            } else {
                $model::on($connection)->create($data);
            }
        } else {
            // Usa query builder direto
            $query = $connection
                ? DB::connection($connection)->table($tableName)
                : DB::table($tableName);

            // Tenta usar updateOrCreate se houver chaves únicas
            $uniqueKeys = $this->getUniqueKeys($data);
            $existingRecord = null;

            if (! empty($uniqueKeys)) {
                $existingRecord = $query->where($uniqueKeys)->first();
            }

            if ($this->shouldGenerateId($data) && ! $existingRecord) {
                $data['id'] = $this->generateId($row);
            }

            if (! empty($uniqueKeys)) {
                if ($existingRecord) {
                    $query->where($uniqueKeys)->update($data);
                } else {
                    $query->insert($data);
                }
            } else {
                $query->insert($data);
            }
        }
    }

    /**
     * Retorna as chaves únicas para updateOrCreate
     */
    protected function getUniqueKeys(array $data): array
    {
        $columns = $this->sheet->getColumns();
        $uniqueKeys = [];

        if (isset($data['id']) && $data['id'] !== '') {
            return ['id' => $data['id']];
        }

        foreach ($columns as $column) {
            $rules = $column->getRules();

            // Se tem regra 'unique', usa como chave
            if (in_array('unique', $rules) && isset($data[$column->getName()])) {
                $uniqueKeys[$column->getName()] = $data[$column->getName()];
            }
        }

        // Se não encontrou unique, tenta usar campos comuns
        if (empty($uniqueKeys)) {
            foreach (['id', 'email', 'slug', 'code', 'ean'] as $key) {
                if (isset($data[$key]) && ! empty($data[$key])) {
                    return [$key => $data[$key]];
                }
            }
        }

        return $uniqueKeys;
    }

    protected function shouldGenerateId(array $data): bool
    {
        if (! $this->sheet->shouldGenerateId()) {
            return false;
        }

        return ! isset($data['id']) || $data['id'] === '';
    }

    protected function generateId(array $row): string
    {
        if ($generatorClass = $this->sheet->getGenerateIdClass()) {
            if (class_exists($generatorClass)) {
                $generator = app($generatorClass);

                if ($generator instanceof GeneratesImportId) {
                    return (string) $generator->generate($row);
                }

                throw new \InvalidArgumentException(sprintf(
                    'O gerador de ID deve implementar %s.',
                    GeneratesImportId::class
                ));
            }
        }

        $callback = $this->sheet->getGenerateIdCallback();

        if (is_callable($callback)) {
            return (string) $callback($row);
        }

        return (string) Str::ulid();
    }

    public function getSuccessfulRows(): int
    {
        return $this->successfulRows;
    }

    public function getFailedRows(): int
    {
        return $this->failedRows;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna os dados relacionados coletados (para sheets relacionadas)
     */
    public function getRelatedData(): array
    {
        return $this->relatedData;
    }

    /**
     * Armazena dados relacionados indexados pela lookupKey
     */
    protected function storeRelatedData(string $lookupValue, array $data): void
    {
        $sheetName = $this->sheet->getName();

        if (! isset($this->relatedData[$sheetName])) {
            $this->relatedData[$sheetName] = [];
        }

        // Armazena ou mescla dados para a mesma chave
        if (isset($this->relatedData[$sheetName][$lookupValue])) {
            $this->relatedData[$sheetName][$lookupValue] = array_merge(
                $this->relatedData[$sheetName][$lookupValue],
                $data
            );
        } else {
            $this->relatedData[$sheetName][$lookupValue] = $data;
        }
    }

    /**
     * Mescla dados da linha atual com dados relacionados de outras sheets
     */
    protected function mergeRelatedData(array $data): array
    {
        // Se não há sheets relacionadas, retorna os dados originais
        if (! $this->sheet->hasRelatedSheets() || empty($this->relatedData)) {
            return $data;
        }

        // Para cada sheet relacionada, busca dados correspondentes
        foreach ($this->sheet->getRelatedSheets() as $relatedSheet) {
            $sheetName = $relatedSheet->getName();
            $lookupKey = $relatedSheet->getLookupKey();

            // Verifica se temos dados relacionados para esta sheet
            if (! isset($this->relatedData[$sheetName]) || ! isset($data[$lookupKey])) {
                continue;
            }

            $lookupValue = $data[$lookupKey];

            // Se encontrou dados relacionados, mescla
            if (isset($this->relatedData[$sheetName][$lookupValue])) {
                $relatedRowData = $this->relatedData[$sheetName][$lookupValue];

                // Remove a lookup key dos dados relacionados para evitar sobrescrever
                unset($relatedRowData[$lookupKey]);

                // Remove valores vazios para não sobrescrever dados da sheet principal
                $relatedRowData = array_filter(
                    $relatedRowData,
                    fn ($value) => ! ($value === null || $value === '')
                );

                // Mescla os dados (dados relacionados têm prioridade)
                $data = array_replace($data, $relatedRowData);
            }
        }

        return $data;
    }
}
