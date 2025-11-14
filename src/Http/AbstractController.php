<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http;

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

    /**
     * Trata erros do método store
     */
    protected function handleStoreError(\Exception $e): RedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->withInput()
            ->with('error', app()->environment('local') ? $e->getMessage() : 'Erro ao criar o item.');
    }

    /**
     * Trata erros do método update
     */
    protected function handleUpdateError(\Exception $e, string $id): RedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->withInput()
            ->with('error', app()->environment('local') ? $e->getMessage() : 'Erro ao atualizar o item.');
    }

    /**
     * Trata erros do método destroy
     */
    protected function handleDestroyError(\Exception $e, string $id): RedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->with('error', app()->environment('local') ? $e->getMessage() : 'Erro ao deletar o item.');
    }
}
