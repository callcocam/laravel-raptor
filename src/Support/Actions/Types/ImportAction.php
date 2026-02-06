<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Events\ImportCompleted;
use Callcocam\LaravelRaptor\Imports\AdvancedImport;
use Callcocam\LaravelRaptor\Imports\DefaultImport;
use Callcocam\LaravelRaptor\Jobs\ProcessAdvancedImport;
use Callcocam\LaravelRaptor\Jobs\ProcessImport;
use Callcocam\LaravelRaptor\Notifications\ImportCompletedNotification;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithSheets;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\CheckboxField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\UploadField;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Support\Table\Confirm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportAction extends ExecuteAction
{
    use WithSheets;
    protected string $method = 'POST';

    protected ?string $modelClass = null;

    protected ?array $columnMapping = null;

    protected bool $useJob = false;

    protected ?string $importClass = null;

    protected bool $useAdvancedImport = false;

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'import');
        $fileName = str($this->getName())->replace('import', 'file')->slug()->toString();
        $this->name($name) // ✅ Sempre define o name
            ->label('Importar')
            ->icon('Upload')
            ->color('blue')
            ->tooltip('Importar registros')
            ->component('action-modal-form')
            ->policy('import')
            ->executeUrlCallback(str($this->name)->replace('import', 'execute')->toString())
            ->columns([
                UploadField::make($fileName, 'Arquivo')
                    ->acceptedFileTypes(['.csv', '.xlsx'])
                    ->required()
                    ->rules(['required', 'file', 'mimes:csv,xlsx', 'max:10240'])
                    ->messages([
                        'required' => 'O arquivo é obrigatório.',
                        'file' => 'Deve ser um arquivo válido.',
                        'mimes' => 'O arquivo deve ser CSV ou XLSX.',
                        'max' => 'O arquivo não pode ser maior que 10MB.',
                    ])->columnSpan('full'),
                // CheckboxField::make('clean_data', 'Limpar dados existentes')
                //     ->default(false)
                //     ->columnSpan('full'),
            ])
            ->confirm(Confirm::make(
                title: 'Importar Registros',
                text: 'Tem certeza que deseja importar os registros?',
                confirmButtonText: 'Sim, Importar',
                cancelButtonText: 'Cancelar',
                successMessage: 'Importação iniciada com sucesso, assim que terminarmos avisaremos você!',
                closeModalOnSuccess: false, // Não fecha o modal automaticamente
            ))
            ->callback(function (Request $request, ?Model $model) {
                $user = $request->user();
                $resourceName = $this->getResourceName();
                dd($resourceName);
                // Obtém o arquivo enviado
                $fileFieldName = str($this->getName())->replace('import', 'file')->slug()->toString();
                $file = $request->file($fileFieldName);

                if (! $file) {
                    return [
                        'notification' => [
                            'title' => 'Erro na Importação',
                            'text' => 'Nenhum arquivo foi enviado.',
                            'type' => 'error',
                        ],
                    ];
                }

                // Salva o arquivo temporariamente
                $fileName = 'imports/'.Str::uuid().'.'.$file->getClientOriginalExtension();
                $file->storeAs('', $fileName, 'local');

                if ($this->shouldUseJob()) {
                    // Verifica se é importação avançada ou simples
                    if ($this->useAdvancedImport && ! empty($this->sheets)) {
                        return $this->dispatchAdvancedImportJob($fileName, $file, $user);
                    }

                    // Job para importação simples (legado)
                    $model = app($this->getModelClass());
                    $connectionName = $model->getConnectionName();
                    $connectionConfig = config("database.connections.{$connectionName}");

                    ProcessImport::dispatch(
                        $fileName,
                        $this->getModelClass(),
                        $this->columnMapping,
                        $this->getImportClass(),
                        $resourceName,
                        $user->id,
                        $connectionName,
                        $connectionConfig
                    );

                    return [
                        'notification' => [
                            'title' => 'Importação Iniciada',
                            'text' => 'Sua importação está sendo processada. Você receberá uma notificação quando estiver concluída.',
                            'type' => 'info',
                        ],
                    ];
                }

                try {
                    // Verifica se deve usar importação avançada (com sheets)
                    if ($this->useAdvancedImport && ! empty($this->sheets)) {
                        return $this->processAdvancedImport($fileName, $file, $user);
                    }

                    // Importação síncrona simples (legado)
                    $importClass = $this->getImportClass();
                    $connection = app($this->getModelClass())->getConnectionName();
                    $import = new $importClass($this->getModelClass(), $this->columnMapping, $connection);

                    Excel::import($import, $fileName, 'local');

                    // Obtém estatísticas da importação (se disponível)
                    $totalRows = $import->getRowCount() ?? 0;
                    $successfulRows = $import->getSuccessfulCount() ?? $totalRows;
                    $failedRows = $import->getFailedCount() ?? 0;

                    // Remove o arquivo temporário
                    if (file_exists(storage_path('app/'.$fileName))) {
                        unlink(storage_path('app/'.$fileName));
                    }

                    // Cria notificação no banco
                    $user->notify(new ImportCompletedNotification($resourceName));

                    // Dispara evento de broadcast para atualização em tempo real
                    event(new ImportCompleted(
                        userId: $user->id,
                        modelName: class_basename($this->getModelClass()),
                        totalRows: $totalRows,
                        successfulRows: $successfulRows,
                        failedRows: $failedRows,
                        fileName: $file->getClientOriginalName()
                    ));

                    return [
                        'notification' => [
                            'title' => 'Importação Concluída',
                            'text' => 'Os registros foram importados com sucesso. Verifique suas notificações.',
                            'type' => 'success',
                        ],
                    ];
                } catch (\Exception $e) {
                    report($e);

                    // Remove o arquivo em caso de erro
                    if (file_exists(storage_path('app/'.$fileName))) {
                        unlink(storage_path('app/'.$fileName));
                    }

                    return [
                        'notification' => [
                            'title' => 'Erro na Importação',
                            'text' => app()->environment('local') ? $e->getMessage() : 'Ocorreu um erro ao importar os registros.',
                            'type' => 'error',
                        ],
                    ];
                }
            });
        $this->setUp();
    }

    public function model(string $modelClass): self
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    public function getModelClass(): string
    {
        if (! $this->modelClass) {
            throw new \Exception('Model class não foi definido para a importação.');
        }

        return $this->modelClass;
    }

    public function columnMapping(array $mapping): self
    {
        $this->columnMapping = $mapping;

        return $this;
    }

    /**
     * Define as sheets para importação avançada
     * Sobrescreve o método da trait para adicionar lógica específica
     */
    public function sheets(array $sheets): static
    {
        // Define as sheets usando a trait WithSheets
        foreach ($sheets as $sheet) {
            if ($sheet instanceof Sheet) {
                $this->addSheet($sheet);
            }
        }

        // Marca como importação avançada
        $this->useAdvancedImport = true;

        // Armazena as sheets também nas columns para compatibilidade
        $this->columns($sheets);

        return $this;
    }

    public function useJob(bool $useJob = true): self
    {
        $this->useJob = $useJob;

        return $this;
    }

    public function shouldUseJob(): bool
    {
        return $this->useJob;
    }

    public function import(string $importClass): self
    {
        $this->importClass = $importClass;

        return $this;
    }

    public function getImportClass(): string
    {
        return $this->importClass ?? DefaultImport::class;
    }

    protected function getResourceName(): string
    {
        $modelName = class_basename($this->getModelClass());

        return Str::plural(Str::lower(str_replace('_', ' ', Str::snake($modelName))));
    }

    /**
     * Processa importação avançada com múltiplas sheets
     */
    protected function processAdvancedImport(string $fileName, $uploadedFile, $user): array
    {
        // Determina a conexão
        $connection = null;
        if ($this->modelClass) {
            $connection = app($this->modelClass)->getConnectionName();
        } elseif (! empty($this->sheets)) {
            $firstSheet = $this->sheets[0] ?? null;
            if ($firstSheet instanceof Sheet) {
                $connection = $firstSheet->getConnection();
            }
        }

        // Cria instância do AdvancedImport
        $import = new AdvancedImport(
            $this->sheets,
            $connection,
            $uploadedFile->getClientOriginalName()
        );

        Excel::import($import, $fileName, 'local');

        // Obtém estatísticas da importação
        $totalRows = $import->getTotalRows();
        $successfulRows = $import->getSuccessfulRows();
        $failedRows = $import->getFailedRows();

        // Remove o arquivo temporário
        if (file_exists(storage_path('app/'.$fileName))) {
            unlink(storage_path('app/'.$fileName));
        }

        // Cria notificação no banco
        $resourceName = $this->getResourceNameFromSheets();
        $user->notify(new ImportCompletedNotification($resourceName));

        // Dispara evento de broadcast para atualização em tempo real
        event(new ImportCompleted(
            userId: $user->id,
            modelName: $resourceName,
            totalRows: $totalRows,
            successfulRows: $successfulRows,
            failedRows: $failedRows,
            fileName: $uploadedFile->getClientOriginalName()
        ));

        return [
            'notification' => [
                'title' => 'Importação Concluída',
                'text' => "Importados: {$successfulRows} | Erros: {$failedRows}. Verifique suas notificações.",
                'type' => $failedRows > 0 ? 'warning' : 'success',
            ],
        ];
    }

    /**
     * Obtém o nome do recurso a partir das sheets
     */
    protected function getResourceNameFromSheets(): string
    {
        if ($this->modelClass) {
            return $this->getResourceName();
        }

        if (! empty($this->sheets)) {
            $firstSheet = $this->sheets[0] ?? null;

            if ($firstSheet instanceof Sheet) {
                if ($modelClass = $firstSheet->getModelClass()) {
                    return Str::plural(Str::lower(str_replace('_', ' ', Str::snake(class_basename($modelClass)))));
                }

                if ($tableName = $firstSheet->getTableName()) {
                    return Str::plural(Str::lower(str_replace('_', ' ', $tableName)));
                }
            }
        }

        return 'registros';
    }

    /**
     * Despacha o job de importação avançada
     */
    protected function dispatchAdvancedImportJob(string $fileName, $uploadedFile, $user): array
    {
        // Determina a conexão
        $connection = null;
        $connectionConfig = null;

        if ($this->modelClass) {
            $model = app($this->modelClass);
            $connection = $model->getConnectionName();
            $connectionConfig = config("database.connections.{$connection}");
        } elseif (! empty($this->sheets)) {
            $firstSheet = $this->sheets[0] ?? null;
            if ($firstSheet instanceof Sheet) {
                $connection = $firstSheet->getConnection();
                if ($connection) {
                    $connectionConfig = config("database.connections.{$connection}");
                }
            }
        }

        // Serializa as sheets para o job
        $sheetsData = $this->serializeSheets($this->sheets);

        // Obtém o nome do recurso
        $resourceName = $this->getResourceNameFromSheets();

        // Despacha o job
        ProcessAdvancedImport::dispatch(
            $fileName,
            $sheetsData,
            $resourceName,
            $user->id,
            $connection,
            $connectionConfig,
            $uploadedFile->getClientOriginalName()
        );

        return [
            'notification' => [
                'title' => 'Importação Iniciada',
                'text' => 'Sua importação está sendo processada em múltiplas planilhas. Você receberá uma notificação quando estiver concluída.',
                'type' => 'info',
            ],
        ];
    }

    /**
     * Serializa as sheets para passar ao job
     */
    protected function serializeSheets(array $sheets): array
    {
        $serialized = [];

        foreach ($sheets as $sheet) {
            if (! $sheet instanceof Sheet) {
                continue;
            }

            $sheetData = [
                'name' => $sheet->getName(),
                'modelClass' => $sheet->getModelClass(),
                'tableName' => $sheet->getTableName(),
                'database' => $sheet->getDatabase(),
                'connection' => $sheet->getConnection(),
                'serviceClass' => $sheet->getServiceClass(),
                'columns' => [],
            ];

            // Serializa as colunas
            foreach ($sheet->getColumns() as $column) {
                $columnData = [
                    'class' => get_class($column),
                    'name' => $column->getName(),
                    'label' => $column->getLabel(),
                    'index' => $column->getIndex(),
                    'rules' => $column->getRules(),
                    'messages' => $column->getMessages(),
                    'default' => $column->getDefaultValue(),
                    'format' => $column->getFormat(),
                    'cast' => $column->getCast(),
                ];

                $sheetData['columns'][] = $columnData;
            }

            $serialized[] = $sheetData;
        }

        return $serialized;
    }

    public function render($model, $request = null): array
    {
        $array = parent::render($model, $request);

        // Remove as colunas do tipo "sheet" para não aparecerem no modal
        if (isset($array['columns']) && is_array($array['columns'])) {
            $array['columns'] = array_values(array_filter($array['columns'], function ($column) {
                return ! isset($column['type']) || $column['type'] !== 'sheet';
            }));
        }

        return $array;
    }
}
