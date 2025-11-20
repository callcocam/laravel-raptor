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
            ->confirm([
                'title' => 'Confirmar exclusão',
                'message' => 'Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.',
                'confirmText' => 'Sim, excluir',
                'cancelText' => 'Cancelar',
                'requiresTypedConfirmation' => false, // Desabilitado por padrão
                'typedConfirmationWord' => 'EXCLUIR', // Palavra padrão
            ])
            ->requiresTypedConfirmation() ;
        $this->setUp();
    }

    /**
     * Ativa a confirmação por digitação
     *
     * @param string|null $word Palavra que deve ser digitada (padrão: "EXCLUIR")
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
}
