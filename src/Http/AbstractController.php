<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers;

use App\Http\Controllers\Controller;

abstract class AbstractController extends Controller
{
    protected function getHeaderActions(): array
    {
        return [
            // Ações de cabeçalho padrão
        ];
    }

    protected function getImportActions(): array
    {
        return [
            // Ações para importação
        ];
    }

    protected function getExportActions(): array
    {
        return [
            // Ações para exportação
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            // Ações de cabeçalho da tabela
        ];
    }
}
