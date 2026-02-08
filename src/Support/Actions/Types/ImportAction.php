<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Services\AdvancedImportDispatcher;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithSheets;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\UploadField;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Support\Table\Confirm;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImportAction extends ExecuteAction
{
    use WithSheets;

    protected string $method = 'POST';

    protected ?string $modelClass = null;

    protected bool $useJob = false;

    protected bool $generateIdEnabled = false;

    protected $generateIdCallback = null;

    protected ?string $generateIdClass = null;

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

                if ($user && empty(config('app.current_tenant_id')) && isset($user->tenant_id)) {
                    config(['app.current_tenant_id' => $user->tenant_id]);
                }

                if ($errorResponse = $this->ensureSheetsConfigured()) {
                    return $errorResponse;
                }

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
                    return $this->dispatchAdvancedImportJob($fileName, $file, $user);
                }

                try {
                    return $this->processAdvancedImport($fileName, $file, $user);
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

        // Armazena as sheets também nas columns para compatibilidade
        $this->columns($sheets);

        return $this;
    }

    public function addSheet(Sheet $sheet): static
    {
        if ($this->generateIdEnabled) {
            if ($this->generateIdClass) {
                $sheet->generateIdUsing($this->generateIdClass);
            }

            $sheet->generateId($this->generateIdCallback);
        }

        $this->sheets[] = $sheet;

        return $this;
    }

    public function generateId(?Closure $callback = null): self
    {
        $this->generateIdEnabled = true;
        $this->generateIdCallback = $callback;

        return $this;
    }

    public function generateIdUsing(string $generatorClass): self
    {
        $this->generateIdEnabled = true;
        $this->generateIdClass = $generatorClass;

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

    /**
     * Processa importação avançada com múltiplas sheets
     */
    protected function processAdvancedImport(string $fileName, $uploadedFile, $user): array
    {
        return app(AdvancedImportDispatcher::class)->processAdvancedImport(
            $fileName,
            $uploadedFile->getClientOriginalName(),
            $user,
            $this->modelClass,
            $this->sheets
        );
    }

    /**
     * Despacha o job de importação avançada
     */
    protected function dispatchAdvancedImportJob(string $fileName, $uploadedFile, $user): array
    {
        // Obtém o nome do recurso
        $resourceName = app(AdvancedImportDispatcher::class)->getResourceNameFromSheets($this->modelClass, $this->sheets);

        return app(AdvancedImportDispatcher::class)->dispatch(
            $fileName,
            $resourceName,
            $user->id,
            $this->modelClass,
            $this->sheets,
            $uploadedFile->getClientOriginalName()
        );
    }

    protected function ensureSheetsConfigured(): ?array
    {
        if (empty($this->sheets)) {
            return [
                'notification' => [
                    'title' => 'Erro na Importação',
                    'text' => 'Nenhuma sheet foi configurada para esta importação.',
                    'type' => 'error',
                ],
            ];
        }

        return null;
    }

    public function render($model, $request = null): array
    {
        $array = parent::render($model, $request);

        if (empty($this->sheets)) {
            $array['disabled'] = true;
            $array['disabledReason'] = 'Nenhuma sheet foi configurada para esta importação.';
        }

        // Remove as colunas do tipo "sheet" para não aparecerem no modal
        if (isset($array['columns']) && is_array($array['columns'])) {
            $array['columns'] = array_values(array_filter($array['columns'], function ($column) {
                return ! isset($column['type']) || $column['type'] !== 'sheet';
            }));
        }

        return $array;
    }
}
