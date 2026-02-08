<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Import\Contracts;

/**
 * Cast customizado para colunas de importação.
 *
 * Quando uma coluna usa ->cast(SuaClasse::class) e a classe implementa esta interface,
 * o valor da célula é transformado chamando format() com nome da coluna, label e linha completa.
 */
interface ImportCastInterface
{
    /**
     * Transforma o valor da célula para o valor a ser persistido no banco.
     *
     * @param  string  $name  Nome da coluna no banco (ex.: 'category_id')
     * @param  string  $label  Cabeçalho da coluna na planilha (ex.: 'categoria')
     * @param  mixed  $value  Valor bruto da célula (já passou por render/default da coluna)
     * @param  array<string, mixed>  $row  Linha completa (todas as colunas da linha, principal + relatedSheets)
     * @return mixed Valor a ser gravado no banco
     */
    public function format(string $name, string $label, mixed $value, array $row): mixed;
}
