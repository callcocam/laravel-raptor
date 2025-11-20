<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Concerns;

use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithColumns;

trait InteractWithForm
{ 

    use WithColumns;
    /**
     * Retorna o formulário como estrutura de dados
     */
    public function getForm(): ?array
    {
        if (empty($this->getColumns())) {
            return [];
        }

        return [
            'columns' => $this->getArrayColumns(),
        ];
    }
}
