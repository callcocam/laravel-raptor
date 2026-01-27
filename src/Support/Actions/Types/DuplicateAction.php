<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

class DuplicateAction extends Action
{
    protected string $actionType = 'api';

    protected string $method = 'POST';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'duplicate');
        $this->name($name)
            ->label('Duplicar')
            ->icon('Copy')
            ->color('blue')
            ->tooltip('Criar uma cópia deste registro')
            ->confirm([
                'title' => 'Confirmar duplicação',
                'message' => 'Tem certeza que deseja duplicar este registro?',
                'confirmText' => 'Sim, duplicar',
                'cancelText' => 'Cancelar',
            ])
            ->executeUrlCallback();
        $this->setUp();
    }
}
