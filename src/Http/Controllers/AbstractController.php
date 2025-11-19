<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers;

use Callcocam\LaravelRaptor\Support\Table\TableBuilder;
use Illuminate\Http\RedirectResponse as BaseRedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

abstract class AbstractController extends ResourceController
{


    abstract protected function table(TableBuilder $table): TableBuilder;
 

    public function index(Request $request)
    {
        dd($this->table(TableBuilder::make($this->model(), 'model'))
            ->request($request)->toArray());
        return Inertia::render(sprintf('admin/%s/index', $this->resourcePath()), [
            'message' => 'Welcome to Laravel Raptor!',
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getResourceLabel(),
            'resourcePluralLabel' => $this->getResourcePluralLabel(),
            'maxWidth' => $this->getMaxWidth(),
            'breadcrumbs' => $this->breadcrumbs(),
            'data' => $this->table(TableBuilder::make($this->model(), 'model'))
            ->request($request)
            ->toArray(),
        ]);
    }

    /**
     * Trata erros do método store
     */
    protected function handleStoreError(\Exception $e): BaseRedirectResponse
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
    protected function handleUpdateError(\Exception $e, string $id): BaseRedirectResponse
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
    protected function handleDestroyError(\Exception $e, string $id): BaseRedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->with('error', app()->environment('local') ? $e->getMessage() : 'Erro ao deletar o item.');
    }
}
