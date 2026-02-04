<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Imports\DefaultImport;
use Callcocam\LaravelRaptor\Jobs\ProcessImport;
use Callcocam\LaravelRaptor\Notifications\ImportCompletedNotification;
use Callcocam\LaravelRaptor\Events\ImportCompleted;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\CheckboxField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\UploadField;
use Callcocam\LaravelRaptor\Support\Table\Confirm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportAction extends ExecuteAction
{

    protected string $method = 'POST';
    protected ?string $modelClass = null;
    protected ?array $columnMapping = null;
    protected bool $useJob = false;
    protected ?string $importClass = null;

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
                
                // Obtém o arquivo enviado
                $fileFieldName = str($this->getName())->replace('import', 'file')->slug()->toString();
                $file = $request->file($fileFieldName);
                
                if (!$file) {
                    return [
                        'notification' => [
                            'title' => 'Erro na Importação',
                            'text' => 'Nenhum arquivo foi enviado.',
                            'type' => 'error',
                        ],
                    ];
                }

                // Salva o arquivo temporariamente
                $fileName = 'imports/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('', $fileName, 'local');

                if ($this->shouldUseJob()) {
                    // Obtém a conexão do modelo
                    $connection = app($this->getModelClass())->getConnectionName();
                    
                    // Envia para fila
                    ProcessImport::dispatch(
                        $fileName,
                        $this->getModelClass(),
                        $this->columnMapping,
                        $this->getImportClass(),
                        $resourceName,
                        $user->id,
                        $connection
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
                    // Importação síncrona
                    $importClass = $this->getImportClass();
                    $connection = app($this->getModelClass())->getConnectionName();
                    $import = new $importClass($this->getModelClass(), $this->columnMapping, $connection);
                    
                    Excel::import($import, $fileName, 'local');

                    // Obtém estatísticas da importação (se disponível)
                    $totalRows = $import->getRowCount() ?? 0;
                    $successfulRows = $import->getSuccessfulCount() ?? $totalRows;
                    $failedRows = $import->getFailedCount() ?? 0;

                    // Remove o arquivo temporário
                    if (file_exists(storage_path('app/' . $fileName))) {
                        unlink(storage_path('app/' . $fileName));
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
                    if (file_exists(storage_path('app/' . $fileName))) {
                        unlink(storage_path('app/' . $fileName));
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
        if (!$this->modelClass) {
            throw new \Exception('Model class não foi definido para a importação.');
        }

        return $this->modelClass;
    }

    public function columnMapping(array $mapping): self
    {
        $this->columnMapping = $mapping;
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
}
