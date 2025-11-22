<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Table\Confirm;

class ImportApiAction extends ExecuteAction
{

    protected string $method = 'POST';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'api');
        $this->name($name) // ✅ Sempre define o name
            ->label('Importar via API')
            ->icon('Upload')
            ->color('blue')
            ->tooltip('Importar registros')
            ->component('action-modal-form')
            ->policy('import')
            ->callback(function ($request) {
                sleep(5); // Simula um processo demorado
                return redirect()->back()->with('success', 'Importação iniciada com sucesso, assim que terminarmos avisaremos você!');
            })
            ->confirm(Confirm::make(
                title: 'Importar Registros',
                text: 'Tem certeza que deseja importar os registros?',
                confirmButtonText: 'Sim, Importar',
                cancelButtonText: 'Cancelar',
                successMessage: 'Importação iniciada com sucesso, assim que terminarmos avisaremos você!',
                closeModalOnSuccess: false, // Não fecha o modal automaticamente
            ));
        $this->setUp();
    }
}
