<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

class ForceDeleteAction extends Action
{
    protected string $actionType = 'api';

    protected string $method = 'DELETE';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'forceDelete');
        $this->name($name)
            ->label('Excluir Permanentemente')
            ->icon('Trash2')
            ->color('red')
            ->tooltip('Excluir este registro permanentemente')
            ->policy("forceDelete")
            ->confirm([
                'title' => 'Confirmar exclusão permanente',
                'message' => 'Tem certeza que deseja excluir este registro permanentemente? Esta ação não pode ser desfeita.',
                'confirmText' => 'Sim, excluir permanentemente',
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
        if (empty($this->getUrl($item))) {
            return false;
        }

        return parent::isVisible($item);
    }
}
