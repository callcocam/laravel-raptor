<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Types\HiddenField; 
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\UploadField; 

class ImportAction extends ExecuteAction
{

    protected string $method = 'POST';

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
            ->callback(function ($request) {
                return redirect()->back()->with('success', 'Importação iniciada com sucesso, assim que terminarmos avisaremos você!');
            })
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
                    ])
            ])
            ->confirm([
                'title' => 'Importar Registros',
                'text' => 'Tem certeza que deseja importar os registros?',
                'confirmButtonText' => 'Sim, Importar',
                'cancelButtonText' => 'Cancelar',
                'successMessage' => 'Importação iniciada com sucesso, assim que terminarmos avisaremos você!'
            ]);
        $this->setUp();
    }
 
}
