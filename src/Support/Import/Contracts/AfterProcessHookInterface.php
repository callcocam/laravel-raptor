<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Contracts;

/**
 * Hook executado uma vez ao final do processamento da sheet.
 * Recebe todas as linhas persistidas com sucesso (id gerado + campos exclude).
 */
interface AfterProcessHookInterface
{
    /**
     * Chamado ao final da sheet com as linhas processadas com sucesso.
     *
     * @param  array<int, array{row: int, data: array<string, mixed>}>  $completedRows  Cada item: row (número da linha), data (dados completos com id e campos exclude)
     */
    public function afterProcess(string $sheetName, array $completedRows): void;
}
