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
    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this->component('action-confirm')
            ->actionType('actions')
            ->method('POST')
            ->confirm([
                'title' => 'Confirmar Ação',
                'text' => 'Tem certeza que deseja confirmar a ação?',
                'confirmButtonText' => 'Sim, Confirmar',
                'cancelButtonText' => 'Cancelar',
                'successMessage' => 'A ação foi confirmada com sucesso.',
            ])
            ->executeUrlCallback(str($this->name)->replace('confirm', 'execute')->toString())
            ->callback(function (?Request $request = null, $model = null) {
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
