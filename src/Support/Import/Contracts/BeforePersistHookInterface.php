<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Hook executado antes de persistir uma linha.
 * Permite alterar os dados ou impedir o persist (retornar null).
 */
interface BeforePersistHookInterface
{
    /**
     * Chamado antes de persistir. Retorna $data (possivelmente modificado) ou null para não persistir a linha.
     *
     * @param  array<string, mixed>  $data  Dados da linha (inclui campos exclude)
     * @param  int  $rowNumber  Número da linha na planilha
     * @return array<string, mixed>|null Dados para persistir, ou null para pular a linha
     */
    public function beforePersist(array $data, int $rowNumber, ?Model $existing): ?array;
}
