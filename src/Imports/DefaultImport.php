<?php

namespace Callcocam\LaravelRaptor\Imports;

use Callcocam\LaravelRaptor\Events\ImportCompleted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class DefaultImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected int $totalRows = 0;
    protected int $successfulRows = 0;
    protected int $failedRows = 0;

    public function __construct(
        protected string $modelClass,
        protected ?array $columnMapping = null,
        protected ?string $connection = null,
        protected ?string $fileName = null
    ) {}

    /**
     * Processa cada linha da importação
     */
    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count();

        foreach ($rows as $row) {
            try {
                $data = $this->mapRow($row);
                
                // Cria instância do modelo com a conexão correta
                $model = app($this->modelClass);
                if ($this->connection) {
                    $model->setConnection($this->connection);
                }
                
                // Cria ou atualiza o registro
                $model::on($this->connection)->updateOrCreate(
                    $this->getUniqueKeys($data),
                    $data
                );

                $this->successfulRows++;
            } catch (\Exception $e) {
                $this->failedRows++;
                report($e);
            }
        }

        // Dispara o evento de conclusão
        $this->dispatchCompletedEvent();
    }

    /**
     * Dispara o evento de importação concluída
     */
    protected function dispatchCompletedEvent(): void
    {
        if ($userId = Auth::id()) {
            ImportCompleted::dispatch(
                userId: $userId,
                modelName: class_basename($this->modelClass),
                totalRows: $this->totalRows,
                successfulRows: $this->successfulRows,
                failedRows: $this->failedRows,
                fileName: $this->fileName
            );
        }
    }

    /**
     * Mapeia os dados da linha conforme o mapeamento de colunas
     */
    protected function mapRow($row): array
    {
        if (!$this->columnMapping) {
            return $row->toArray();
        }

        $data = [];
        foreach ($this->columnMapping as $excelColumn => $modelColumn) {
            if (isset($row[$excelColumn])) {
                $data[$modelColumn] = $row[$excelColumn];
            }
        }

        return $data;
    }

    /**
     * Retorna as chaves únicas para updateOrCreate
     * Por padrão, usa apenas o primeiro campo não-nulo
     */
    protected function getUniqueKeys(array $data): array
    {
        // Tenta usar 'id', 'email', ou 'slug' como chave única
        foreach (['id', 'email', 'slug', 'code'] as $key) {
            if (isset($data[$key])) {
                return [$key => $data[$key]];
            }
        }

        // Se não encontrar nenhuma chave conhecida, usa a primeira chave disponível
        $firstKey = array_key_first($data);
        return $firstKey ? [$firstKey => $data[$firstKey]] : [];
    }

    /**
     * Tamanho do batch para inserção
     */
    public function batchSize(): int
    {
        return 500;
    }

    /**
     * Tamanho do chunk para leitura
     */
    public function chunkSize(): int
    {
        return 500;
    }
}
