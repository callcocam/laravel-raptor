<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Closure;

/**
 * ModalAction - Abre um modal com formulário e envia via Inertia.js
 *
 * Exemplo de uso:
 * ModalAction::make('assign')
 *     ->label('Atribuir Departamento')
 *     ->icon('UserPlus')
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
class TableAction extends ModalAction
{
    protected string $actionType = 'actions';

    protected string|Closure|null $modalTitle = null;

    protected string|Closure|null $modalDescription = null;

    protected string|Closure|null $modalContent = null;

    protected string|Closure|null $modalType = 'normal';

    protected string|Closure|null $slideoverPosition = 'right';

    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? 'modal');
        $this
            ->actionType('actions')
            ->component('action-modal-form')
            ->method('POST')
            ->policy('modal')
            ->modalSize('md');
    }

    /**
     * Detecta o tipo de colunas (form, table, ou infolist)
     */
    protected function detectColumnType(): string
    {

        return 'table';
    }
}
