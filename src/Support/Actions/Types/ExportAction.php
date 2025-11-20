<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;
 

class ExportAction extends ExecuteAction
{
    protected string $method = 'POST';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'export');
        $this->name($name) // ✅ Sempre define o name
            ->label('Exportar')
            ->icon('Download')
            ->color('green')
            ->tooltip('Exportar registros')
            ->callback(function ($request) {
                return redirect()->back()->with('success', 'Exportação realizada com sucesso!');
            })
            ->component('action-confirm')
            ->confirm([
                'title' => 'Exportar Registros',
                'text' => 'Tem certeza que deseja exportar os registros?',
                'confirmButtonText' => 'Sim, Exportar',
                'cancelButtonText' => 'Cancelar',
                'successMessage' => 'Exportação realizada com sucesso!',
            ]);
        $this->setUp();
    }
}
