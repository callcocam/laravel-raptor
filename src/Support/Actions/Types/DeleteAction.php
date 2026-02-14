<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

class DeleteAction extends Action
{
    protected string $method = 'DELETE';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'delete');
        $this->name($name) // ✅ Sempre define o name
            ->label('Excluir')
            ->icon('Trash2')
            ->color('red')
            ->tooltip('Excluir registro')
            ->policy('delete')
            ->confirm([
                'title' => 'Confirmar exclusão',
                'message' => 'Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.',
                'confirmText' => 'Sim, excluir',
                'cancelText' => 'Cancelar',
                'requiresTypedConfirmation' => false, // Desabilitado por padrão
                'typedConfirmationWord' => 'EXCLUIR', // Palavra padrão
            ])->hidden(fn ($record) => ! empty($record->deleted_at))
            ->requiresTypedConfirmation();
        $this->setUp();
    }

    /**
     * Ativa a confirmação por digitação
     *
     * @param  string|null  $word  Palavra que deve ser digitada (padrão: "EXCLUIR")
     * @return $this
     */
    public function requiresTypedConfirmation(?string $word = null): self
    {
        $currentConfirm = $this->confirm;

        $currentConfirm['requiresTypedConfirmation'] = true;

        if ($word !== null) {
            $currentConfirm['typedConfirmationWord'] = strtoupper($word);
        }

        return $this->confirm($currentConfirm);
    }

    /**
     * Desativa a confirmação por digitação
     *
     * @return $this
     */
    public function withoutTypedConfirmation(): self
    {
        $currentConfirm = $this->confirm;
        $currentConfirm['requiresTypedConfirmation'] = false;

        return $this->confirm($currentConfirm);
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
