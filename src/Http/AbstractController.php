<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

abstract class AbstractController extends Controller
{
    use AuthorizesRequests;

    /**
     * Define o model que será usado pelo controller
     */
    abstract protected function model(): string;

    /**
     * Define o resource path para as views
     */
    abstract protected function resourcePath(): string;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        try {
            $model = $this->model();
            $query = $model::query();

            // Permite customização da query através de método hook
            if (method_exists($this, 'indexQuery')) {
                $query = $this->indexQuery($query, $request);
            }

            $items = $query->paginate(
                $request->input('per_page', 15)
            );

            return Inertia::render($this->resourcePath() . '/index.vue', [
                'items' => $items,
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'index');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        try {
            return Inertia::render($this->resourcePath() . '/create.vue');
        } catch (\Exception $e) {
            return $this->handleError($e, 'create');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $model = $this->model();
            
            // Valida os dados através de método hook
            $validated = method_exists($this, 'validateStore')
                ? $this->validateStore($request)
                : $request->all();

            $item = $model::create($validated);

            // Hook após criação
            if (method_exists($this, 'afterStore')) {
                $this->afterStore($item, $request);
            }

            return redirect()
                ->route($this->resourcePath() . '.index')
                ->with('success', 'Item criado com sucesso!');
        } catch (\Exception $e) {
            return $this->handleStoreError($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): Response
    {
        try {
            $model = $this->model();
            $item = $model::findOrFail($id);

            return Inertia::render($this->resourcePath() . '/show.vue', [
                'item' => $item,
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'show');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): Response
    {
        try {
            $model = $this->model();
            $item = $model::findOrFail($id);

            return Inertia::render($this->resourcePath() . '/edit.vue', [
                'item' => $item,
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'edit');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        try {
            $model = $this->model();
            $item = $model::findOrFail($id);

            // Valida os dados através de método hook
            $validated = method_exists($this, 'validateUpdate')
                ? $this->validateUpdate($request, $item)
                : $request->all();

            $item->update($validated);

            // Hook após atualização
            if (method_exists($this, 'afterUpdate')) {
                $this->afterUpdate($item, $request);
            }

            return redirect()
                ->route($this->resourcePath() . '.index')
                ->with('success', 'Item atualizado com sucesso!');
        } catch (\Exception $e) {
            return $this->handleUpdateError($e, $id);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $model = $this->model();
            $item = $model::findOrFail($id);

            // Hook antes de deletar
            if (method_exists($this, 'beforeDestroy')) {
                $this->beforeDestroy($item);
            }

            $item->delete();

            // Hook após deletar
            if (method_exists($this, 'afterDestroy')) {
                $this->afterDestroy($id);
            }

            return redirect()
                ->route($this->resourcePath() . '.index')
                ->with('success', 'Item deletado com sucesso!');
        } catch (\Exception $e) {
            return $this->handleDestroyError($e, $id);
        }
    }

    /**
     * Trata erros de métodos que retornam Response
     */
    protected function handleError(\Exception $e, string $action): Response
    {
        // Log do erro
        report($e);

        return Inertia::render('Error', [
            'status' => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
            'message' => app()->environment('local') ? $e->getMessage() : 'Ocorreu um erro.',
            'action' => $action,
        ]);
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
