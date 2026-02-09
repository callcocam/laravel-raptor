<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Imports\AdvancedImport;
use Callcocam\LaravelRaptor\Jobs\ProcessAdvancedImport;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithSheets;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\UploadField;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Support\Table\Confirm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action de importação via Excel (CSV/XLSX).
 *
 * Apenas recebe e armazena as sheets; cada Sheet define suas colunas,
 * geração de ID (generateIdUsing) e relatedSheets opcionais.
 * Processamento (principal + relatedSheets, ignorar abas ausentes) fica no Service/Job.
 *
 * @see docs/export-import/import-advanced-plan.md
 */
class ImportAction extends ExecuteAction
{
    use WithSheets;

    protected string $method = 'POST';

    protected ?string $modelClass = null;

    protected bool $useJob = false;

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

                // Salva o arquivo temporariamente (disco local: app/imports/{nome})
                $fileName = sprintf('%s.%s', Str::uuid(), $file->getClientOriginalExtension());
                $filePath = 'imports/'.$fileName;
                $file->storeAs('imports', $fileName, 'local');

                if ($this->shouldUseJob()) {
                    return $this->dispatchAdvancedImportJob($filePath, $file, $user);
                }

                try {
                    return $this->processAdvancedImport($filePath, $file, $user);
                } catch (\Exception $e) {
                    report($e);

                    $disk = Storage::disk('local');
                    if ($disk->exists($filePath)) {
                        unlink($disk->path($filePath));
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
     * Define as sheets para importação avançada.
     * Cada sheet configura sua própria geração de ID via generateIdUsing(Classe::class).
     */
    public function sheets(array $sheets): static
    {
        foreach ($sheets as $sheet) {
            if ($sheet instanceof Sheet) {
                $this->addSheet($sheet);
            }
        }

        $this->columns($sheets);

        return $this;
    }

    public function addSheet(Sheet $sheet): static
    {
        $this->sheets[] = $sheet;

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
     * Processa importação síncrona (sem job): mesmo fluxo do Job (AdvancedImport + process).
     */
    protected function processAdvancedImport(string $filePath, $uploadedFile, $user): array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($filePath)) {
            return [
                'notification' => [
                    'title' => 'Erro na Importação',
                    'text' => 'Arquivo não encontrado.',
                    'type' => 'error',
                ],
            ];
        }

        $fullPath = $disk->path($filePath);

        $context = [
            'tenant_id' => config('app.current_tenant_id'),
            'user_id' => $user->getKey(),
        ];

        $sheets = $this->getMainSheets();
        $import = new AdvancedImport($sheets, null, $context);
        Excel::import($import, $filePath, 'local');
        $import->process();

        $totalRows = $import->getTotalRows();
        $successfulRows = $import->getSuccessfulRows();
        $failedRows = $import->getFailedRows();

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $message = $totalRows > 0
            ? sprintf(
                'Importação concluída: %d registro(s) processado(s) (%d com sucesso, %d com erro).',
                $totalRows,
                $successfulRows,
                $failedRows
            )
            : 'Nenhum registro encontrado nas abas configuradas.';

        return [
            'notification' => [
                'title' => 'Importação concluída',
                'text' => $message,
                'type' => $failedRows > 0 ? 'warning' : 'success',
            ],
        ];
    }

    /**
     * Despacha o job de importação (processamento assíncrono).
     */
    protected function dispatchAdvancedImportJob(string $filePath, $uploadedFile, $user): array
    {
        $context = [
            'tenant_id' => config('app.current_tenant_id'),
            'user_id' => $user->getKey(),
        ];

        ProcessAdvancedImport::dispatch(
            $filePath,
            $this->getSheetsPayload(),
            $this->getResourceName(),
            $user->getKey(),
            $uploadedFile->getClientOriginalName(),
            $context
        );

        return [
            'notification' => [
                'title' => 'Importação enfileirada',
                'text' => 'O arquivo será processado em background. Você será notificado ao concluir.',
                'type' => 'success',
            ],
        ];
    }

    /**
     * Payload das sheets principais (cada uma com suas relatedSheets) para o Job.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getSheetsPayload(): array
    {
        $main = $this->getMainSheets();

        return array_map(fn (Sheet $sheet) => $sheet->toArray(), $main);
    }

    /**
     * Nome do recurso para notificação (ex.: "Product", "Category").
     */
    protected function getResourceName(): string
    {
        $main = array_values($this->getMainSheets());
        $first = $main[0] ?? null;

        if ($first instanceof Sheet && $first->getModelClass()) {
            return class_basename($first->getModelClass());
        }

        return 'Importação';
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
