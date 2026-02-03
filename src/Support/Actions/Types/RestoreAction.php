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

    /**
     * Verifica visibilidade geral (todas as camadas)
     *
     * ORDEM DE VALIDAÇÃO:
     * 1. Callback customizado (visibilityCallback)
     * 2. Laravel Policy (se definida via ->policy())
     * 3. Visibilidade geral ($visible)
     */
    public function isVisible($item = null): bool
    {
        // Se o registro não estiver excluído, a ação de restaurar não deve ser visível
        if (empty($item->url)) {
            return false;
        }

        return parent::isVisible($item);
    }
}
