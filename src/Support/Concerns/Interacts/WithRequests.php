<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

trait WithRequests
{
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
