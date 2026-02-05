<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers;

use Callcocam\LaravelRaptor\Support\Form\Form;
use Callcocam\LaravelRaptor\Support\Info\InfoList;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse as BaseRedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class AbstractController extends ResourceController
{
    use AuthorizesRequests;


    abstract protected function table(TableBuilder $table): TableBuilder;

    abstract protected function form(Form $form): Form;

    protected function queryBuilder(): Builder
    {
        return app($this->model())->newQuery();
    }


    protected function infolist(InfoList $infoList): InfoList
    {
        return $infoList;
    }


    public function index(Request $request)
    {
        // Verifica autorização via Policy (viewAny)
        $this->authorize('viewAny', $this->model());
        
        $data = $this->table(TableBuilder::make($this->queryBuilder(), 'model'))
            ->request($request)
            ->toArray();

        // Storage::disk('local')->put('raptor.json', json_encode($data));
        return Inertia::render(sprintf('admin/%s/index', $this->resourcePath()), [
            'message' => $this->getSubtitle(),
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getTitle(),
            'resourcePluralLabel' => $this->getTitle(),
            'maxWidth' => $this->getMaxWidth(),
            'breadcrumbs' => $this->breadcrumbs(),
            'table' => $data,
            'actionName' => __('Listagem de :resource', ['resource' => $this->getTitle()]),
        ]);
    }

    public function create(Request $request)
    {
        // Verifica autorização via Policy (create)
        $this->authorize('create', $this->model());

        $model  = app($this->model());

        return Inertia::render(sprintf('admin/%s/create', $this->resourcePath()), [
            'message' => $this->getSubtitle(),
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getTitle(),
            'resourcePluralLabel' => $this->getResourcePluralLabel(),
            'maxWidth' => $this->getMaxWidth(),
            'breadcrumbs' => $this->breadcrumbs(),
            'form' => $this->form(Form::make($model, 'model')->defaultActions($this->getFormActions()))->render(),
            'pageHeaderActions' => collect($this->getPageHeaderActions($model, 'create'))
                ->map(fn($action) => $action->render($model, $request))
                ->filter(fn($action) => $action['visible'] ?? true)
                ->values()
                ->toArray(),
            'action' => $this->getFormDefaultStoreAction($request->route()->getAction('as'), null),
            'actionName' => __('Criar :resource', ['resource' => $this->getTitle()]),
        ]);
    }

    public function store(Request $request): BaseRedirectResponse
    {
        try {
            $model  = app($this->model());
            
            // Verifica autorização via Policy (create)
            $this->authorize('create', $model);

            // Hook: antes de criar
            $this->beforeCreate($request);

            // Extrai as regras de validação dos campos do formulário
            $form = $this->form(Form::make($model, 'model'));

            // Prepara os dados ANTES da validação (converte valores formatados)
            $preparedData = $form->prepareDataForValidation($request, null);

            $validationRules = array_merge(
                $form->getValidationRules(),
                $this->rules()
            );
            $validationMessages = $form->getValidationMessages();

            // Valida os dados já preparados
            $validator = \Illuminate\Support\Facades\Validator::make(
                $preparedData,
                $validationRules,
                $validationMessages
            );

            $validated = $this->beforeExtraStore($validator->validate(), $request);

            $record = $model->create($validated);

            $form->saveRelatedData($validated, $record, $request);

            // Hook: depois de criar
            $this->afterCreate($request, $record);

            $route = str($request->route()->getAction('as'))->replaceLast('.store', '.edit')->toString();
            $route = $this->getRedirectRouteAfterStore($route, $record);
            return redirect()->route($route, [
                'record' => $record->getKey(),
            ])
                ->with('success', 'Item criado com sucesso.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-lança exceção de validação para o Laravel tratar
            throw $e;
        } catch (\Exception $e) {
            return $this->handleStoreError($e);
        }
    }

    public function show(Request $request, string $record)
    {
        $model = $this->model()::findOrFail($record);
        
        // Verifica autorização via Policy (view)
        $this->authorize('view', $model);

        return Inertia::render(sprintf('admin/%s/show', $this->resourcePath()), [
            'message' => $this->getSubtitle(),
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getTitle(),
            'resourcePluralLabel' => $this->getResourcePluralLabel(),
            'maxWidth' => $this->getMaxWidth(),
            'breadcrumbs' => $this->breadcrumbs(),
            'model' => $model,
            'infolist' => $this->infolist(InfoList::make($model, 'model'))->render($model),
            'pageHeaderActions' => collect($this->getPageHeaderActions($model, 'show'))
                ->map(fn($action) => $action->render($model, $request))
                ->filter(fn($action) => $action['visible'] ?? true)
                ->values()
                ->toArray(),
            'actionName' => __('Visualizar :resource', ['resource' => $this->getTitle()]),
        ]);
    }

    public function edit(Request $request, string $record)
    {
        $model = $this->model()::findOrFail($record);
        
        // Verifica autorização via Policy (update)
        $this->authorize('update', $model);
        
        return Inertia::render(sprintf('admin/%s/edit', $this->resourcePath()), [
            'message' => $this->getSubtitle(),
            'resourceName' => $this->getResourceName(),
            'resourcePluralName' => $this->getResourcePluralName(),
            'resourceLabel' => $this->getTitle(),
            'resourcePluralLabel' => $this->getTitle(),
            'maxWidth' => $this->getMaxWidth(),
            'breadcrumbs' => $this->breadcrumbs(),
            'model' => $model,
            'form' => $this->form(Form::make($model, 'model')->model($model)->defaultActions($this->getFormActions()))->render($model),
            'pageHeaderActions' => collect($this->getPageHeaderActions($model, 'edit'))
                ->map(fn($action) => $action->render($model, $request))
                ->filter(fn($action) => $action['visible'] ?? true)
                ->values()
                ->toArray(),
            'actionName' => __('Editar :resource', ['resource' => $this->getTitle()]),
            'action' => $this->getFormDefaultUpdateAction($request->route()->getAction('as'), $model->getKey()),
        ]);
    }

    public function update(Request $request, string $record): BaseRedirectResponse
    {

        try {
            $model = $this->model()::findOrFail($record);
            
            // Verifica autorização via Policy (update)
            $this->authorize('update', $model);

            // Hook: antes de atualizar
            $this->beforeUpdate($request, $record);

            // Extrai as regras de validação dos campos do formulário
            $form = $this->form(Form::make($model, 'model'));

            // Prepara os dados ANTES da validação (converte valores formatados)
            $preparedData = $form->prepareDataForValidation($request, $model);

            $validationRules = array_merge(
                $form->getValidationRules($model, $request),
                $this->rules($model)
            );
            $validationMessages = $form->getValidationMessages();

            // Valida os dados já preparados
            $validator = \Illuminate\Support\Facades\Validator::make(
                $preparedData,
                $validationRules,
                $validationMessages
            );

            $validated = $this->beforeExtraUpdate($validator->validate(), $request, $model);

            $model->update($validated);

            //Vamo fazer atualizações de relacionados se necessário
            $form->updateRelatedData($validated, $model, $request);

            // Hook: depois de atualizar
            $this->afterUpdate($request, $model);

            $route = str($request->route()->getAction('as'))->replaceLast('.update', '.edit')->toString();
            $route = $this->getRedirectRouteAfterUpdate($route, $model);

            return redirect()->route(
                $route,
                [
                    'record' => $model->getKey(),
                ],
            )->with('success', 'Item atualizado com sucesso.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-lança exceção de validação para o Laravel tratar
            throw $e;
        } catch (\Exception $e) {
            return $this->handleUpdateError($e, $record);
        }
    }

    public function destroy(string $record): BaseRedirectResponse
    {
        try {
            $model = $this->model()::findOrFail($record);
            
            // Verifica autorização via Policy (delete)
            $this->authorize('delete', $model);

            // Hook: antes de deletar
            $this->beforeDelete($record);

            $model->delete();

            // Hook: depois de deletar
            $this->afterDelete($record, $model);

            $route = str(request()->route()->getAction('as'))->replaceLast('.destroy', '.index')->toString();
            $route = $this->getRedirectRouteAfterDestroy($route);

            return redirect()->route($route)
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
            
            // Verifica autorização via Policy (restore)
            $this->authorize('restore', $model);

            // Hook: antes de restaurar
            $this->beforeRestore($record);

            $model->restore();

            // Hook: depois de restaurar
            $this->afterRestore($record, $model);

            $route = str(request()->route()->getAction('as'))->replaceLast('.restore', '.index')->toString();
            $route = $this->getRedirectRouteAfterRestore($route);

            return redirect()->route($route)
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
            
            // Verifica autorização via Policy (forceDelete)
            $this->authorize('forceDelete', $model);

            // Hook: antes de deletar permanentemente
            $this->beforeForceDelete($record);

            $model->forceDelete();

            // Hook: depois de deletar permanentemente
            $this->afterForceDelete($record, $model);

            $route = str(request()->route()->getAction('as'))->replaceLast('.force-delete', '.index')->toString();
            $route = $this->getRedirectRouteAfterForceDelete($route);

            return redirect()->route($route)
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

            // Hook: antes de ação em massa
            $this->beforeBulkAction($request, $ids);

            // Chama método dinâmico baseado na action
            $methodName = 'bulk' . ucfirst($action);

            if (method_exists($this, $methodName)) {
                $result = $this->$methodName($ids);

                // Hook: depois de ação em massa
                $this->afterBulkAction($request, $ids);

                return $result;
            }

            return redirect()
                ->back()
                ->with('error', 'Ação em massa não implementada.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-lança exceção de validação para o Laravel tratar
            throw $e;
        } catch (\Exception $e) {
            return $this->handleBulkActionError($e);
        }
    }

    /**
     * Executa uma ação personalizada (header ou bulk)
     * Busca a action pelo nome e executa seu callback
     */
    public function execute(Request $request): BaseRedirectResponse|JsonResponse|BinaryFileResponse
    {
        try {
            // Valida os campos básicos da action
            $validated = $request->validate([
                'actionType' => 'required|string',
                'actionName' => 'required|string',
                'fieldName' => 'nullable|string',
                'record' => 'nullable|string', // Ajuste o modelo conforme necessário
            ]);
            $type = data_get($validated, 'actionType');
            $actionName = data_get($validated, 'actionName');
            $recordId = data_get($validated, 'record');
            $fieldName = data_get($validated, 'fieldName');
            if (!$type || !$actionName) {
                return redirect()
                    ->back()
                    ->with('error', 'Parâmetros inválidos para execução da ação.');
            }

            if ($recordId) {
                $model = $this->model()::findOrFail($recordId);
            } else {
                $model = null;
            }


            $actions = match ($type) {
                'header' => collect($this->table(TableBuilder::make($this->model(), 'model'))->getHeaderActions()),
                'bulk' => collect($this->table(TableBuilder::make($this->model(), 'model'))->getBulkActions()),
                'actions' => collect($this->table(TableBuilder::make($this->model(), 'model'))->getActions()),
                'column' => collect($this->table(TableBuilder::make($this->model(), 'model'))->getColumns())->filter(fn($column) => $column->getName() === $fieldName),
                'form' => collect($this->form(Form::make($model))->getActions()),
                'field' => collect($this->form(Form::make($model))->getColumns())->filter(fn($column) => $column->getName() === $fieldName)->flatMap(function ($column) {
                    return $column->getActions();
                }),
                default => collect([])
            };
            $callback = $actions->filter(fn($action) => $action->getName() === $actionName)->first();
            if (!$callback) {
                return redirect()
                    ->back()
                    ->with('error', 'Ação não encontrada.');
            } 
            // Extrai as regras de validação dos campos da action
            $validationRules = $callback->getValidationRules();
            $validationMessages = $callback->getValidationMessages();

            // Valida os dados do formulário da action se houver regras
            if (!empty($validationRules)) {
                $request->validate($validationRules, $validationMessages);
            }

            // Hook: antes de executar action
            $this->beforeExecute($request, $actionName, $model);

            if (!method_exists($callback, 'executeCallback')) {
                return redirect()
                    ->back()
                    ->with('error', 'Ação não possui um callback definido.');
            }
            // Executa o callback da action
            $result = $callback->executeCallback($request, $model);
           
            if (!$result) { 
                return redirect()
                    ->back()
                    ->with('error', 'Ação não retornou nenhum resultado.'); 
            }
          
            // Hook: depois de executar action
            $this->afterExecute($request, $actionName, $model, $result instanceof BaseRedirectResponse ? null : $result);

            if ($result instanceof BinaryFileResponse) {
                return $result;
            }
            if (is_string($result)) {
                return redirect()->to($result);
            }
            if ($result instanceof BaseRedirectResponse) {
                return $result;
            }
            if (is_array($result)) {
                $notification = data_get($result, 'notification', []);
                $type = data_get($notification, 'type', 'success');
                $message = data_get($notification, 'text') ?? data_get($notification, 'message', 'Ação executada com sucesso.');
                
                return redirect()->back()->with($type, $message);
            }
            return redirect()->back()
                ->with('success', 'Ação executada com sucesso.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-lança exceção de validação para o Laravel tratar
            throw $e;
        } catch (\Exception $e) {
            return $this->handleExecuteError($e, $request->input('actionName', 'desconhecida'));
        }
    }
    /**
     * Define as regras de validação para store/update
     */
    protected function rules(?string $id = null): array
    {
        return [];
    }

    protected function beforeExtraStore(array $data, \Illuminate\Http\Request $request)
    {
        return $data;
    }

    protected function beforeExtraUpdate(array $data, \Illuminate\Http\Request $request, Model $model)
    {
        return $data;
    }

    protected function beforeCreate(Request $request)
    {
        //
    }

    protected function beforeUpdate(Request $request, string $id)
    {
        //
    }

    protected function beforeDelete(string $id)
    {
        //
    }

    protected function beforeRestore(string $id)
    {
        //
    }

    protected function beforeForceDelete(string $id)
    {
        //
    }

    protected function beforeBulkAction(Request $request, array $ids)
    {
        //
    }

    protected function beforeImport(Request $request)
    {
        //
    }

    protected function beforeExport(Request $request)
    {
        //
    }

    protected function beforeExecute(Request $request, string $action, ?Model $model = null)
    {
        //
    }

    protected function afterExecute(Request $request, string $action, ?Model $model = null, $result = null)
    {
        //
    }

    protected function afterImport(Request $request)
    {
        //
    }

    protected function afterExport(Request $request)
    {
        //
    }

    protected function afterBulkAction(Request $request, array $ids)
    {
        //
    }

    protected function afterCreate(Request $request, $model)
    {
        //
    }

    protected function afterUpdate(Request $request, $model)
    {
        //
    }

    protected function afterDelete(string $id, $model)
    {
        //
    }

    protected function afterRestore(string $id, $model)
    {
        //
    }

    protected function afterForceDelete(string $id, $model)
    {
        //
    }



    /**
     * Retorna as rotas padrão do formulário (store/update)
     */
    protected function getFormUrlAction(string $routeName, ?string $id = null): string
    {

        if ($id) {
            return route($routeName, ['record' => $id]);
        }

        return route($routeName);
    }

    /**
     * Retorna a ação padrão do formulário store
     */
    protected function getFormDefaultStoreAction(string $action, ?string $id = null): string
    {
        $routeName = str($action)
            ->replace('create', 'store')
            ->toString();

        return $this->getFormUrlAction($routeName, $id);
    }

    /**
     * Retorna a ação padrão do formulário update
     */
    protected function getFormDefaultUpdateAction(string $action, ?string $id = null): string
    {
        $routeName = str($action)
            ->replace('edit', 'update')
            ->toString();

        return $this->getFormUrlAction($routeName, $id);
    }

    /**
     * Retorna a rota de redirecionamento após o store (para edit)
     * Pode ser sobrescrito para customizar o redirecionamento
     */
    protected function getRedirectRouteAfterStore(string $routeName, Model $record): string
    {
        return $routeName;
    }

    /**
     * Retorna a rota de redirecionamento após o update
     * Pode ser sobrescrito para customizar o redirecionamento
     */
    protected function getRedirectRouteAfterUpdate(string $routeName, Model $record): string
    {
        return $routeName;
    }

    /**
     * Retorna a rota de redirecionamento após o destroy
     * Pode ser sobrescrito para customizar o redirecionamento
     */
    protected function getRedirectRouteAfterDestroy(string $routeName): string
    {
        return $routeName;
    }

    /**
     * Retorna a rota de redirecionamento após o restore
     * Pode ser sobrescrito para customizar o redirecionamento
     */
    protected function getRedirectRouteAfterRestore(string $routeName): string
    {
        return $routeName;
    }

    /**
     * Retorna a rota de redirecionamento após o forceDelete
     * Pode ser sobrescrito para customizar o redirecionamento
     */
    protected function getRedirectRouteAfterForceDelete(string $routeName): string
    {
        return $routeName;
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
