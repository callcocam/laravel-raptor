<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait HasAuthorization
{
    /**
     * Verifica se o usuário pode visualizar o modelo
     */
    protected function canView(?Model $model = null): bool
    {
        if (! $model) {
            return false;
        }

        // Se não houver usuário autenticado, nega acesso
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Verifica se existe uma policy registrada para o modelo
        if (Gate::getPolicyFor($model)) {
            return $user->can('view', $model);
        }

        // Se não houver policy, permite por padrão (pode ser ajustado conforme necessidade)
        return $this->defaultPermission();
    }

    /**
     * Verifica se o usuário pode criar um novo modelo
     */
    protected function canCreate(string $modelClass): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (Gate::getPolicyFor($modelClass)) {
            return $user->can('create', $modelClass);
        }

        return $this->defaultPermission();
    }

    /**
     * Verifica se o usuário pode atualizar o modelo
     */
    protected function canUpdate(?Model $model = null): bool
    {
        if (! $model) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (Gate::getPolicyFor($model)) {
            return $user->can('update', $model);
        }

        return $this->defaultPermission();
    }

    /**
     * Alias para canUpdate (compatibilidade)
     */
    protected function canEdit(?Model $model = null): bool
    {
        return $this->canUpdate($model);
    }

    /**
     * Verifica se o usuário pode deletar o modelo (soft delete)
     */
    protected function canDelete(?Model $model = null): bool
    {
        if (! $model) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (Gate::getPolicyFor($model)) {
            return $user->can('delete', $model);
        }

        return $this->defaultPermission();
    }

    /**
     * Verifica se o usuário pode restaurar o modelo
     */
    protected function canRestore(?Model $model = null): bool
    {
        if (! $model) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Verifica se o modelo usa SoftDeletes
        if (! method_exists($model, 'trashed')) {
            return false;
        }

        if (Gate::getPolicyFor($model)) {
            return $user->can('restore', $model);
        }

        return $this->defaultPermission();
    }

    /**
     * Verifica se o usuário pode deletar permanentemente o modelo
     */
    protected function canForceDelete(?Model $model = null): bool
    {
        if (! $model) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Verifica se o modelo usa SoftDeletes
        if (! method_exists($model, 'trashed')) {
            return false;
        }

        if (Gate::getPolicyFor($model)) {
            return $user->can('forceDelete', $model);
        }

        return $this->defaultPermission();
    }

    /**
     * Verifica se o usuário pode realizar ações em massa (bulk actions)
     */
    protected function canBulkAction(?Model $model = null, string $action = 'delete'): bool
    {
        if (! $model) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Verifica permissão específica para bulk action se existir
        $bulkAbility = 'bulk'.ucfirst($action);

        if (Gate::getPolicyFor($model) && method_exists(Gate::getPolicyFor($model), $bulkAbility)) {
            return $user->can($bulkAbility, $model);
        }

        // Fallback para a permissão individual da ação
        return match ($action) {
            'delete' => $this->canDelete($model),
            'restore' => $this->canRestore($model),
            'forceDelete' => $this->canForceDelete($model),
            default => $this->defaultPermission()
        };
    }

    /**
     * Verifica se o usuário pode exportar dados
     */
    protected function canExport(string $modelClass): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (Gate::getPolicyFor($modelClass)) {
            $policy = Gate::getPolicyFor($modelClass);
            if (method_exists($policy, 'export')) {
                return $user->can('export', $modelClass);
            }
        }

        return $this->defaultPermission();
    }

    /**
     * Verifica se o usuário pode importar dados
     */
    protected function canImport(string $modelClass): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (Gate::getPolicyFor($modelClass)) {
            $policy = Gate::getPolicyFor($modelClass);
            if (method_exists($policy, 'import')) {
                return $user->can('import', $modelClass);
            }
        }

        return $this->defaultPermission();
    }

    /**
     * Verifica múltiplas permissões de uma vez
     *
     * @param  array  $abilities  ['view', 'update', 'delete']
     * @return array ['view' => true, 'update' => false, 'delete' => false]
     */
    protected function checkAbilities(?Model $model, array $abilities): array
    {
        $results = [];

        foreach ($abilities as $ability) {
            $method = 'can'.ucfirst($ability);

            if (method_exists($this, $method)) {
                $results[$ability] = $this->$method($model);
            } else {
                $results[$ability] = false;
            }
        }

        return $results;
    }

    /**
     * Retorna a permissão padrão quando não há policy definida
     *
     * Pode ser sobrescrito em controllers específicos para mudar o comportamento padrão
     */
    protected function defaultPermission(): bool
    {
        // Por padrão, permite acesso se não houver policy
        // Altere para false se quiser negar acesso por padrão
        return true;
    }

    /**
     * Nega acesso se o usuário não tiver permissão
     * Útil para uso em controllers
     */
    protected function authorizeOrFail(string $ability, ?Model $model = null): void
    {
        $method = 'can'.ucfirst($ability);

        if (method_exists($this, $method) && ! $this->$method($model)) {
            abort(403, 'Você não tem permissão para realizar esta ação.');
        }
    }

    /**
     * Retorna todas as permissões disponíveis para um modelo
     * Útil para debugging ou interface administrativa
     */
    protected function getModelAbilities(?Model $model = null): array
    {
        if (! $model) {
            return [];
        }

        return [
            'view' => $this->canView($model),
            'update' => $this->canUpdate($model),
            'delete' => $this->canDelete($model),
            'restore' => $this->canRestore($model),
            'forceDelete' => $this->canForceDelete($model),
        ];
    }
}
