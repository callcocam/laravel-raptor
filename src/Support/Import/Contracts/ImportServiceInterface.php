<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Contracts;

use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;

/**
 * Contrato do service de importação para uma Sheet.
 *
 * Cada Sheet pertence a uma tabela; relatedSheets são abas com colunas da mesma tabela.
 * O service processa linhas (dados da principal + relatedSheets mesclados por lookupKey).
 */
interface ImportServiceInterface
{
    /**
     * Processa uma linha: aplicar defaults, gerar ID se configurado, validar, persistir na tabela da Sheet.
     *
     * @param  array<string, mixed>  $row  Dados da linha (principal + relatedSheets já mesclados)
     * @param  int  $rowNumber  Número da linha na planilha (para mensagens de erro)
     */
    public function processRow(array $row, int $rowNumber): void;

    /**
     * Quantidade de linhas processadas com sucesso.
     */
    public function getSuccessfulRows(): int;

    /**
     * Quantidade de linhas que falharam (validação ou persistência).
     */
    public function getFailedRows(): int;

    /**
     * Lista de erros por linha (ex.: ['linha' => 2, 'message' => '...']).
     *
     * @return array<int, array{row: int, message: string, column?: string}>
     */
    public function getErrors(): array;

    /**
     * Linhas que falharam com dados originais (para gerar Excel de erros).
     *
     * @return array<int, array{row: int, data: array<string, mixed>, message: string}>
     */
    public function getFailedRowsData(): array;

    /**
     * Linhas persistidas com sucesso (para o hook afterProcess: row + data com id e campos exclude).
     *
     * @return array<int, array{row: int, data: array<string, mixed>}>
     */
    public function getCompletedRows(): array;

    /**
     * Define contexto para colunas hidden (tenant_id, user_id, etc.).
     * Valores são aplicados às colunas com defaultValue antes de persistir.
     *
     * @param  array<string, mixed>  $context  Ex.: ['tenant_id' => '...', 'user_id' => 1]
     */
    public function setContext(array $context): static;

    /**
     * Sheet que este service processa (uma tabela; pode ter relatedSheets com colunas da mesma tabela).
     */
    public function getSheet(): Sheet;
}
