<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers;

use Callcocam\LaravelRaptor\Support\Form\Form;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;
use Illuminate\Http\RedirectResponse as BaseRedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

abstract class AbstractController extends ResourceController
{


    abstract protected function table(TableBuilder $table): TableBuilder;

    abstract protected function form(Form $form): Form;


    public function index(Request $request)
    {
        $data = $this->table(TableBuilder::make($this->model(), 'model'))
            ->request($request)
            ->toArray();
        // Storage::disk('local')->put('raptor.json', json_encode($data));
        return Inertia::render(sprintf('admin/%s/index', $this->resourcePath()), [
            'message' => 'Welcome to Laravel Raptor!',
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getResourceLabel(),
            'resourcePluralLabel' => $this->getResourcePluralLabel(),
            'maxWidth' => $this->getMaxWidth(),
            'breadcrumbs' => $this->breadcrumbs(),
            'table' => $data,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render(sprintf('admin/%s/create', $this->resourcePath()), [
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getResourceLabel(),
            'resourcePluralLabel' => $this->getResourcePluralLabel(),
            'maxWidth' => $this->getMaxWidth(),
            'breadcrumbs' => $this->breadcrumbs(),
            'form' => $this->form(Form::make($this->model(), 'model')),
        ]);
    }

    public function store(Request $request): BaseRedirectResponse
    {
        try {
            $validated = $request->validate($this->rules());
            
            $model = $this->model()::create($validated);

            return redirect()
                ->route(sprintf('%s.index', $this->getResourceName()))
                ->with('success', 'Item criado com sucesso.');
        } catch (\Exception $e) {
            return $this->handleStoreError($e);
        }
    }

    public function show(Request $request, string $id)
    {
        $model = $this->model()::findOrFail($id);

        return Inertia::render(sprintf('admin/%s/show', $this->resourcePath()), [
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getResourceLabel(),
            'resourcePluralLabel' => $this->getResourcePluralLabel(),
            'maxWidth' => $this->getMaxWidth(),
            'breadcrumbs' => $this->breadcrumbs(),
            'model' => $model,
            'infolist' => [],
        ]);
    }

    public function edit(Request $request, string $id)
    {
        $model = $this->model()::findOrFail($id);

        return Inertia::render(sprintf('admin/%s/edit', $this->resourcePath()), [
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getResourceLabel(),
            'resourcePluralLabel' => $this->getResourcePluralLabel(),
            'maxWidth' => $this->getMaxWidth(),
            'breadcrumbs' => $this->breadcrumbs(),
            'model' => $model,
            'form' => $this->form(Form::make($this->model(), 'model')),
        ]);
    }

    public function update(Request $request, string $id): BaseRedirectResponse
    {
        try {
            $model = $this->model()::findOrFail($id);
            
            $validated = $request->validate($this->rules($id));
            
            $model->update($validated);

            return redirect()
                ->route(sprintf('%s.index', $this->getResourceName()))
                ->with('success', 'Item atualizado com sucesso.');
        } catch (\Exception $e) {
            return $this->handleUpdateError($e, $id);
        }
    }

    public function destroy(string $id): BaseRedirectResponse
    {
        try {
            $model = $this->model()::findOrFail($id);
            
            $model->delete();

            return redirect()
                ->route(sprintf('%s.index', $this->getResourceName()))
                ->with('success', 'Item deletado com sucesso.');
        } catch (\Exception $e) {
            return $this->handleDestroyError($e, $id);
        }
    }

    /**
     * Define as regras de validação para store/update
     */
    protected function rules(?string $id = null): array
    {
        return [];
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
