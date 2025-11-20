<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\UploadField;
use Callcocam\LaravelRaptor\Support\Form\Concerns\InteractWithForm;
use Illuminate\Support\Facades\Route;

class ImportAction extends Action
{
    use InteractWithForm;

    protected string $method = 'POST';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'import');
        $fileName = str($this->getName())->slug()->toString();
        $this->name($name) // ✅ Sempre define o name
            ->label('Importar')
            ->icon('Upload')
            ->color('blue')
            ->actionType('header')
            ->tooltip('Importar registros')
            ->component('action-modal-form')
            ->url(function () {
                $name = str($this->getName())->replace('import', 'execute');
                $route = sprintf('%s.%s', $this->getRequest()->getContext(), $name);
                if (Route::has($route)) {
                    return route($route);
                }
                return null;
            })
            ->callback(function ($request) {
                return redirect()->back()->with('success', 'Importação iniciada com sucesso, assim que terminarmos avisaremos você!');
            })
            ->columns([
                UploadField::make($fileName, 'Arquivo')->acceptedFileTypes(['.csv', '.xlsx'])->required()
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

    public function toArray(): array
    {
        $array = array_merge(parent::toArray(), $this->getForm());

        return $array;
    }
}
