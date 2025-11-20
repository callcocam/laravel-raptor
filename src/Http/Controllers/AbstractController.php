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

    public function show(Request $request, string $record)
    {
        $model = $this->model()::findOrFail($record);

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

    public function edit(Request $request, string $record)
    {
        $model = $this->model()::findOrFail($record);

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

    public function update(Request $request, string $record): BaseRedirectResponse
    {
        try {
            $model = $this->model()::findOrFail($record);

            $validated = $request->validate($this->rules($record));

            $model->update($validated);

            return redirect()
                ->route(sprintf('%s.index', $this->getResourceName()))
                ->with('success', 'Item atualizado com sucesso.');
        } catch (\Exception $e) {
            return $this->handleUpdateError($e, $record);
        }
    }

    public function destroy(string $record): BaseRedirectResponse
    {
        try {
            $model = $this->model()::findOrFail($record);

            $model->delete();

            return redirect()
                ->route(sprintf('%s.index', $this->getResourceName()))
                ->with('success', 'Item deletado com sucesso.');
        } catch (\Exception $e) {
            return $this->handleDestroyError($e, $record);
        }
    }

    /**
     * Restaura um registro soft deleted
     */
    public function restore(string $record): BaseRedirectResponse
    {
        try {
            $model = $this->model()::withTrashed()->findOrFail($record);
            
            $model->restore();

            return redirect()
                ->route(sprintf('%s.index', $this->getResourceName()))
                ->with('success', 'Item restaurado com sucesso.');
        } catch (\Exception $e) {
            return $this->handleRestoreError($e, $record);
        }
    }

    /**
     * Deleta permanentemente um registro
     */
    public function forceDelete(string $record): BaseRedirectResponse
    {
        try {
            $model = $this->model()::withTrashed()->findOrFail($record);
            
            $model->forceDelete();

            return redirect()
                ->route(sprintf('%s.index', $this->getResourceName()))
                ->with('success', 'Item excluído permanentemente.');
        } catch (\Exception $e) {
            return $this->handleForceDeleteError($e, $record);
        }
    }

    /**
     * Executa ação em massa (bulk action)
     */
    public function bulkAction(Request $request): BaseRedirectResponse
    {
        try {
            $action = $request->input('action');
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return redirect()
                    ->back()
                    ->with('error', 'Nenhum item selecionado.');
            }

            // Chama método dinâmico baseado na action
            $methodName = 'bulk' . ucfirst($action);
            
            if (method_exists($this, $methodName)) {
                return $this->$methodName($ids);
            }

            return redirect()
                ->back()
                ->with('error', 'Ação em massa não implementada.');
        } catch (\Exception $e) {
            return $this->handleBulkActionError($e);
        }
    }

    /**
     * Executa uma ação personalizada (header ou bulk)
     * Busca a action pelo nome e executa seu callback
     */
    public function execute(Request $request): BaseRedirectResponse
    {
        try {
            $type = $request->input('actionType');
            $actionName = $request->input('actionName');

           
            $actions = match($type) {
                'header' =>  collect($this->table(TableBuilder::make($this->model(), 'model'))->getHeaderActions()),
                'bulk' =>  collect($this->table(TableBuilder::make($this->model(), 'model'))->getBulkActions()),
                default => collect([])
            };

            $callback = $actions->filter(fn($action) => $action->getName() === $actionName)->first(); 
            
            if ($callback) {
                $callback->execute($request);
                return $callback->execute($request);
            }

            return redirect()
                ->back()
                ->with('error', 'Ação não implementada.');
        } catch (\Exception $e) {
            return $this->handleExecuteError($e, $request->input('action', 'desconhecida'));
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

    /**
     * Trata erros do método restore
     */
    protected function handleRestoreError(\Exception $e, string $id): BaseRedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->with('error', app()->environment('local') ? $e->getMessage() : 'Erro ao restaurar o item.');
    }

    /**
     * Trata erros do método forceDelete
     */
    protected function handleForceDeleteError(\Exception $e, string $id): BaseRedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->with('error', app()->environment('local') ? $e->getMessage() : 'Erro ao excluir permanentemente o item.');
    }

    /**
     * Trata erros do método bulkAction
     */
    protected function handleBulkActionError(\Exception $e): BaseRedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->with('error', app()->environment('local') ? $e->getMessage() : 'Erro ao executar ação em massa.');
    }

    /**
     * Trata erros do método execute
     */
    protected function handleExecuteError(\Exception $e, string $action): BaseRedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->with('error', app()->environment('local') ? $e->getMessage() : "Erro ao executar a ação: {$action}.");
    }

    /**
     * Trata erros do método import
     */
    protected function handleImportError(\Exception $e): BaseRedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->with('error', app()->environment('local') ? $e->getMessage() : 'Erro ao importar o item.');
    }

    /**
     * Trata erros do método export
     */
    protected function handleExportError(\Exception $e): BaseRedirectResponse
    {
        report($e);

        return redirect()
            ->back()
            ->with('error', app()->environment('local') ? $e->getMessage() : 'Erro ao exportar o item.');
    }
}
