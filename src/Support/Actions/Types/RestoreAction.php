<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

class RestoreAction extends Action
{
    protected string $actionType = 'api';

    protected string $method = 'POST';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'restore');
        $this->name($name)
            ->label('Restaurar')
            ->icon('RotateCcw')
            ->color('blue')
            ->tooltip('Restaurar registro excluído')
            ->policy("restore")
            ->confirm([
                'title' => 'Confirmar restauração',
                'message' => 'Tem certeza que deseja restaurar este registro?',
                'confirmText' => 'Sim, restaurar',
                'cancelText' => 'Cancelar',
            ])
            ->hidden(fn($record) => empty($record->deleted_at));
        $this->setUp();
    }
}
