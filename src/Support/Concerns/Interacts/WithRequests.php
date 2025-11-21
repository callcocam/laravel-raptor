<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\Concerns\HasAuthorization;
use Illuminate\Database\Eloquent\Model;

trait WithRequests
{
    use HasAuthorization;

    /**
     * Retorna as ações do header da página (show, edit, create, etc)
     * Dependendo do contexto da página
     */
    protected function getPageHeaderActions(?Model $model = null, string $page = 'index'): array
    {
        return match ($page) {
            'show' => $this->getShowPageActions($model),
            'edit' => $this->getEditPageActions($model),
            'create' => $this->getCreatePageActions(),
            'index' => $this->getIndexPageActions(),
            default => []
        };
    }

    /**
     * Ações disponíveis na página Show
     * 
     * Nota: As actions usam BelongsToVisible trait para controlar sua visibilidade.
     * Você pode customizar a visibilidade de cada action usando:
     * - ->policy('update') - Usa Laravel Policy
     * - ->visible(fn($model) => ...) - Callback customizado
     * - ->visibleWhenShow(true/false) - Por contexto
     */
    protected function getShowPageActions(?Model $model = null): array
    {
        if (!$model) {
            return [];
        }

        $resourceName = $this->getResourcePluralName();

        $actions = [
            // Edit Action - usa policy('update') por padrão
            \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make("{$resourceName}.edit")
                ->size('sm')
                ->variant('outline')
                ->policy('update'),

            // Delete Action - usa policy('delete') + verifica se não está deletado
            \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make("{$resourceName}.destroy")
                ->size('sm')
                ->variant('destructive')
                ->policy('delete'),

            // Restore Action - usa policy('restore') + verifica se está deletado
            \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make("{$resourceName}.restore")
                ->size('sm')
                ->variant('default')
                ->policy('restore'),

            // Force Delete Action - usa policy('forceDelete') + verifica se está deletado
            \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make("{$resourceName}.forceDelete")
                ->size('sm')
                ->variant('destructive')
                ->policy('forceDelete'),
        ];

        return $actions;
    }

    /**
     * Ações disponíveis na página Edit
     * 
     * Nota: As actions usam BelongsToVisible trait para controlar sua visibilidade.
     */
    protected function getEditPageActions(?Model $model = null): array
    {
        if (!$model) {
            return [];
        }

        $resourceName = $this->getResourcePluralName();

        $actions = [
            // View Action - usa policy('view')
            \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make("{$resourceName}.show")
                ->size('sm')
                ->variant('outline')
                ->policy('view'),

            // Delete Action - usa policy('delete') + verifica se não está deletado
            \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make("{$resourceName}.destroy")
                ->size('sm')
                ->variant('destructive')
                ->policy('delete'),
        ];

        return $actions;
    }

    /**
     * Ações disponíveis na página Create
     */
    protected function getCreatePageActions(): array
    {
        // Geralmente não há ações no header da página de criação
        return [];
    }

    /**
     * Ações disponíveis na página Index (já existente via table)
     */
    protected function getIndexPageActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Ações de cabeçalho padrão
        ];
    }

    public function getFormActions(): array
    {
        $resource = $this->getResourcePluralName();
        return [
            // Ações para o formulário
            \Callcocam\LaravelRaptor\Support\Actions\Types\CancelAction::make('cancel')
                ->variant('destructive')
                ->url(function ($request) use ($resource) {
                    return redirect()->route(sprintf('%s.%s.index', $request->getContext(), $resource));
                })
                ->size('sm')
                ->label(sprintf("Voltar %s", $this->getResourcePluralLabel())),
            \Callcocam\LaravelRaptor\Support\Actions\Types\SubmitAction::make('submit')
                ->variant('default')
                ->size('sm')
                ->label(sprintf("Salvar %s", $this->getResourceLabel()))
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
