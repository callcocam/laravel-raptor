<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Illuminate\Http\Request;


class ConfirmAction extends ExecuteAction
{

    public function __construct(?string $name = null, ?string $message = null)
    {
        parent::__construct($name, $message);

        $this->component('confirm-action')->confirm([
            'title' => 'Confirmar Ação',
            'text' => 'Tem certeza que deseja confirmar a ação?',
            'confirmButtonText' => 'Sim, Confirmar',
            'cancelButtonText' => 'Cancelar',
            'successMessage' => 'A ação foi confirmada com sucesso.',
        ])
        ->executeUrlCallback(str($this->name)->replace('confirm', 'execute')->toString())
        ->callback(function (Request $request, $model = null) {
            return [
                'notification' => [
                    'title' => 'A ação foi confirmada com sucesso.',
                    'message' => 'A ação foi confirmada com sucesso.',
                    'text' => 'A ação foi confirmada com sucesso.',
                    'type' => 'success',
                ],
            ];
        });
    }
}
