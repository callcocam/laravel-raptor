<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Hook executado após persistir uma linha (criar ou atualizar).
 */
interface AfterPersistHookInterface
{
    /**
     * Chamado após persistir a linha.
     *
     * @param  array<string, mixed>  $data  Dados da linha (inclui campos exclude)
     * @param  int  $rowNumber  Número da linha na planilha
     */
    public function afterPersist(Model $model, array $data, int $rowNumber): void;
}
