<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

/**
 * CallbackAction - Executa uma função JavaScript sem fazer chamada ao backend
 *
 * Exemplo de uso:
 * CallbackAction::make('print', 'printUser')
 *     ->label('Imprimir')
 *     ->icon('Printer')
 *
 * No JavaScript (window function):
 * window.printUser = (action, record) => {
 *     console.log('Printing user:', record)
 *     window.print()
 * }
 */
class CallbackAction extends Action
{
    protected string $actionType = 'callback';

    public function __construct(?string $name = null, ?string $callback = null)
    {
        parent::__construct($name ?? 'callback');
        $this->component('action-button');

        if ($callback) {
            $this->callback($callback);
        }
    }
}
