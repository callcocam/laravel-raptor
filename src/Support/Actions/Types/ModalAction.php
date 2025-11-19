<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

/**
 * ModalAction - Abre um modal com formulário e envia via Inertia.js
 *
 * Exemplo de uso:
 * ModalAction::make('assign')
 *     ->label('Atribuir Departamento')
 *     ->icon('UserPlus')
 *     ->url(fn($user) => route('users.assign', $user))
 *     ->columns([
 *         TextInput::make('department_id')
 *             ->label('Departamento')
 *             ->required(),
 *         Textarea::make('notes')
 *             ->label('Observações'),
 *     ])
 *     ->modalSize('lg')
 *     ->confirm([
 *         'title' => 'Atribuir Usuário',
 *         'confirmText' => 'Atribuir',
 *     ])
 */
class ModalAction extends Action
{
    protected string $actionType = 'modal';

    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? 'modal');
        $this->component('action-modal-form');
        $this->modalSize('md');
    }
}
