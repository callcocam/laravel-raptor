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

/**
 * Service de importação para sheets hierárquicas (ex.: categorias com segmento > departamento > categoria).
 *
 * Por linha: processa cada nível na ordem de hierarchicalColumns; para cada nível com valor
 * faz find-or-create (contexto + pai + valor); o id do nível é usado como pai do próximo.
 * Acumula em completedRows com o id do último nível criado/encontrado.
 */
class HierarchicalImportService extends DefaultImportService
{
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
                $this->successfulRows++;

                return;
            }

            $lastModel = null;
            DB::transaction(function () use ($data, $order, &$lastModel): void {
                $parentId = null;
                $valueColumn = $this->sheet->getHierarchicalValueColumn();
                $parentColumnName = $this->sheet->getParentColumnName();

                foreach ($order as $columnName) {
                    $value = $data[$columnName] ?? null;
                    if ($value === null || (is_string($value) && trim($value) === '')) {
                        continue;
                    }
                    $value = is_string($value) ? trim($value) : $value;

                    $lastModel = $this->findOrCreateLevel($parentId, $valueColumn, $value, $parentColumnName);
                    if ($lastModel instanceof Model) {
                        $parentId = $lastModel->getKey();
                    }
                }
            });

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
                    $allMessages[] = ($attribute ? "{$attribute}: " : '') . $message;
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
     *
     * @param  mixed  $parentId  ID do pai (null para raiz)
     */
    protected function findOrCreateLevel(mixed $parentId, string $valueColumn, mixed $value, string $parentColumnName): ?Model
    {
        $modelClass = $this->sheet->getModelClass();
        if (! $modelClass || ! class_exists($modelClass)) {
            return null;
        }

        $model = app($modelClass);
        if ($this->connection ?? $this->sheet->getConnection()) {
            $model->setConnection($this->connection ?? $this->sheet->getConnection());
        }

        $attributes = array_merge($this->context, [
            $parentColumnName => $parentId,
            $valueColumn => $value,
        ]);

        $instance = $model->newQuery()->firstOrCreate($attributes, $attributes);

        return $instance instanceof Model ? $instance : null;
    }
}
