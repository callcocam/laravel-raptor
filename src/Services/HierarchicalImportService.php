<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Support\Import\Contracts\AfterPersistHookInterface;
use Callcocam\LaravelRaptor\Support\Import\Contracts\BeforePersistHookInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de importação para sheets hierárquicas (ex.: categorias com segmento > departamento > categoria).
 *
 * Por linha: processa cada nível na ordem de hierarchicalColumns; para cada nível com valor
 * faz find-or-create (contexto + pai + valor); o id do nível é usado como pai do próximo.
 * Acumula em completedRows com o id do último nível criado/encontrado.
 *
 * Performance: usa cache em memória para evitar queries repetidas de níveis já processados.
 */
class HierarchicalImportService extends DefaultImportService
{
    /**
     * Cache de níveis já processados nesta importação.
     * Chave: ID gerado ou hash(context+parent+value)
     * Valor: Model do nível
     *
     * @var array<string, Model>
     */
    protected array $levelsCache = [];

    /**
     * Processa uma linha hierárquica: monta dados, valida, executa beforePersist, depois
     * find-or-create por nível na ordem de hierarchicalColumns; após cada nível o id vira pai do próximo.
     */
    public function processRow(array $row, int $rowNumber): void
    {
        if ($this->isEmptyRow($row)) {
            return;
        }

        try {
            $data = $this->buildDataFromRow($row);

            $this->validate($data, $rowNumber, null);

            $beforeClass = $this->sheet->getBeforePersistClass();
            if ($beforeClass && class_exists($beforeClass)) {
                $hook = app($beforeClass);
                if ($hook instanceof BeforePersistHookInterface) {
                    $data = $hook->beforePersist($data, $rowNumber, null);
                    if ($data === null) {
                        return;
                    }
                }
            }

            $order = $this->resolveHierarchicalOrder();
            if ($order === []) {
                Log::info('HierarchicalImport: ordem vazia, nenhum nível a processar', [
                    'sheet' => $this->sheet->getName(),
                    'row' => $rowNumber,
                ]);
                $this->successfulRows++;

                return;
            }

            $lastModel = null;
            $connection = $this->getEffectiveConnection();
            $runTransaction = function () use ($data, $order, &$lastModel): void {
                $parentId = null;
                $valueColumn = $this->sheet->getHierarchicalValueColumn();
                $parentColumnName = $this->sheet->getParentColumnName();
                $hierarchyPath = []; // Acumula o caminho hierárquico

                foreach ($order as $levelIndex => $columnName) {
                    $value = $data[$columnName] ?? null;
                    if ($value === null || (is_string($value) && trim($value) === '')) {
                        continue;
                    }
                    $value = is_string($value) ? trim($value) : $value;

                    // Adiciona o valor atual ao caminho hierárquico
                    $hierarchyPath[] = $value;

                    $lastModel = $this->findOrCreateLevel(
                        $parentId,
                        $valueColumn,
                        $value,
                        $parentColumnName,
                        $columnName,
                        $levelIndex,
                        $hierarchyPath,
                        $data
                    );
                    if ($lastModel instanceof Model) {
                        $parentId = $lastModel->getKey();
                    }
                }
            };
            if ($connection) {
                DB::connection($connection)->transaction($runTransaction);
            } else {
                DB::transaction($runTransaction);
            }

            $dataForCompleted = $data;
            if ($lastModel instanceof Model) {
                $dataForCompleted['id'] = $lastModel->getKey();
            }
            $this->completedRows[] = ['row' => $rowNumber, 'data' => $dataForCompleted];

            $afterClass = $this->sheet->getAfterPersistClass();
            if ($afterClass && class_exists($afterClass) && $lastModel instanceof Model) {
                $hook = app($afterClass);
                if ($hook instanceof AfterPersistHookInterface) {
                    $hook->afterPersist($lastModel, $data, $rowNumber);
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
     * Ordem das colunas hierárquicas: explícita na Sheet ou derivada de dependsOn nas colunas.
     *
     * @return array<int, string>
     */
    protected function resolveHierarchicalOrder(): array
    {
        $explicit = $this->sheet->getHierarchicalColumns();
        if ($explicit !== null && $explicit !== []) {
            return $explicit;
        }

        $columns = $this->sheet->getColumns();
        $byDepends = [];
        $rootNames = [];
        foreach ($columns as $column) {
            if (! method_exists($column, 'getDependsOn') || ! method_exists($column, 'getName')) {
                continue;
            }
            $name = $column->getName();
            $parent = $column->getDependsOn();
            if ($parent === null || $parent === '') {
                $rootNames[] = $name;
            } else {
                $byDepends[$parent][] = $name;
            }
        }
        if ($rootNames === [] && $byDepends === []) {
            return [];
        }

        $ordered = [];
        $queue = array_values($rootNames);
        $seen = array_flip($queue);
        while ($queue !== []) {
            $current = array_shift($queue);
            $ordered[] = $current;
            foreach ($byDepends[$current] ?? [] as $child) {
                if (! isset($seen[$child])) {
                    $seen[$child] = true;
                    $queue[] = $child;
                }
            }
        }

        return $ordered;
    }

    /**
     * Find-or-create um nível: busca por contexto + pai + valor; cria se não existir.
     * Usa cache em memória para evitar queries repetidas.
     *
     * @param  mixed  $parentId  ID do pai (null para raiz)
     * @param  string  $valueColumn  Nome da coluna que recebe o valor
     * @param  mixed  $value  Valor do nível
     * @param  string  $parentColumnName  Nome da coluna FK do pai
     * @param  string  $columnName  Nome da coluna Excel (para level_name)
     * @param  int  $levelIndex  Índice do nível na hierarquia (para level)
     * @param  array  $hierarchyPath  Caminho hierárquico completo até este nível
     * @param  array  $originalData  Dados originais da linha (para o generator)
     */
    protected function findOrCreateLevel(
        mixed $parentId,
        string $valueColumn,
        mixed $value,
        string $parentColumnName,
        string $columnName,
        int $levelIndex,
        array $hierarchyPath = [],
        array $originalData = []
    ): ?Model {
        $modelClass = $this->sheet->getModelClass();
        if (! $modelClass || ! class_exists($modelClass)) {
            return null;
        }

        // Gera o ID determinístico (se configurado) para usar como chave de cache
        $generatedId = null;
        if ($this->sheet->shouldGenerateId()) {
            $dataForGenerator = array_merge($originalData, $this->context, [
                'hierarchy_path' => implode(' > ', $hierarchyPath),
                $valueColumn => $value,
                $parentColumnName => $parentId,
                'level_index' => $levelIndex,
                'column_name' => $columnName,
            ]);

            $generatedId = $this->generateId($dataForGenerator);
        }

        // Monta chave de cache (ID gerado ou hash dos atributos de busca)
        $cacheKey = $generatedId ?? $this->buildCacheKey($parentId, $value);

        // Verifica cache APENAS quando usa firstOrCreate (sem ID gerado)
        // Quando usa updateOrCreate (com ID), sempre faz a query para atualizar campos do hook
        if ($generatedId === null && isset($this->levelsCache[$cacheKey])) {
            return $this->levelsCache[$cacheKey];
        }

        $model = app($modelClass);
        if ($this->connection ?? $this->sheet->getConnection()) {
            $model->setConnection($this->connection ?? $this->sheet->getConnection());
        }

        // Atributos de busca: identificam a unicidade do registro
        $searchAttributes = array_merge($this->context, [
            $parentColumnName => $parentId,
            $valueColumn => $value,
        ]);

        // Atributos adicionais: preenchidos na criação ou atualização
        $additionalAttributes = [];

        // Inclui dados do hook (status, etc.) mas remove colunas hierárquicas
        $hierarchicalColumns = $this->sheet->getHierarchicalColumns() ?? [];
        foreach ($originalData as $key => $val) {
            // Ignora colunas hierárquicas (já tratadas acima)
            if (in_array($key, $hierarchicalColumns, true)) {
                continue;
            }
            // Ignora campos de contexto (já incluídos em searchAttributes)
            if (array_key_exists($key, $this->context)) {
                continue;
            }
            // Ignora campos especiais
            if (in_array($key, [$parentColumnName, $valueColumn], true)) {
                continue;
            }
            // Adiciona campo do hook (ex: status) aos attributes
            $additionalAttributes[$key] = $val;
        }

        if ($generatedId !== null) {
            $additionalAttributes['id'] = $generatedId;
        }

        // Adiciona level_name se configurado (slug do nome da coluna Excel)
        $levelNameColumn = $this->sheet->getLevelNameColumn();
        if ($levelNameColumn !== null) {
            $additionalAttributes[$levelNameColumn] = \Illuminate\Support\Str::slug($columnName, '_');
        }

        // Adiciona level se configurado (índice do nível na hierarquia)
        $levelIndexColumn = $this->sheet->getLevelIndexColumn();
        if ($levelIndexColumn !== null) {
            $additionalAttributes[$levelIndexColumn] = $levelIndex;
        }

        // Se gerou ID determinístico, usa updateOrCreate pelo ID (sempre atualiza)
        // Isso garante que reimportar atualiza os campos (level_name, level, etc.)
        if ($generatedId !== null) {
            $allAttributes = array_merge($searchAttributes, $additionalAttributes);
            $instance = $model->newQuery()->updateOrCreate(['id' => $generatedId], $allAttributes);
        } else {
            // Se não gerou ID, busca por context + parent + value; se não achar, cria
            $allAttributes = array_merge($searchAttributes, $additionalAttributes);
            $instance = $model->newQuery()->firstOrCreate($searchAttributes, $allAttributes);
        }

        // Armazena no cache para próximas linhas
        if ($instance instanceof Model) {
            $this->levelsCache[$cacheKey] = $instance;
        }

        return $instance instanceof Model ? $instance : null;
    }

    /**
     * Gera uma chave de cache única para um nível baseado em context + parent + value.
     */
    protected function buildCacheKey(mixed $parentId, mixed $value): string
    {
        $contextKey = md5(json_encode($this->context));
        $parentKey = $parentId ?? 'root';
        $valueKey = is_string($value) ? $value : json_encode($value);

        return "{$contextKey}:{$parentKey}:{$valueKey}";
    }

    /**
     * Limpa o cache de níveis (útil para testes ou processos longos).
     */
    public function clearLevelsCache(): void
    {
        $this->levelsCache = [];
    }

    /**
     * Retorna estatísticas do cache para debug/monitoramento.
     *
     * @return array{cached_levels: int, memory_usage: string}
     */
    public function getCacheStats(): array
    {
        return [
            'cached_levels' => count($this->levelsCache),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
        ];
    }

    /**
     * Formata bytes em formato legível (KB, MB, GB).
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }
}
