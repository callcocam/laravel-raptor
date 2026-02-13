<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\AbstractController;
use Callcocam\LaravelRaptor\Support\Form\Form;
use Callcocam\LaravelRaptor\Support\Info\InfoList;
use Callcocam\LaravelRaptor\Support\Pages\Create;
use Callcocam\LaravelRaptor\Support\Pages\Edit;
use Callcocam\LaravelRaptor\Support\Pages\Execute;
use Callcocam\LaravelRaptor\Support\Pages\Index;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;

class InspirationController extends AbstractController
{
    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.landlord.models.inspiration', \Callcocam\LaravelRaptor\Models\Inspiration::class);
    }

    public function getPages(): array
    {
        return [
            'index' => Index::route(config('raptor.controllers.inspirations.index.route', '/inspirations'))
                ->label(config('raptor.controllers.inspirations.index.label', __('Inspirations')))
                ->name(config('raptor.controllers.inspirations.index.name', 'inspirations.index'))
                ->icon(config('raptor.controllers.inspirations.index.icon', 'FolderTree'))
                ->group(config('raptor.controllers.inspirations.index.group', 'Sistema'))
                ->groupCollapsible(config('raptor.controllers.inspirations.index.groupCollapsible', true))
                ->order(config('raptor.controllers.inspirations.index.order', 20))
                ->middlewares(config('raptor.controllers.inspirations.index.middlewares', ['auth', 'verified'])),
            'create' => Create::route('/inspirations/create')
                ->label(config('raptor.controllers.inspirations.create.label', __('Criar Inspiration')))
                ->name(config('raptor.controllers.inspirations.create.name', 'inspirations.create'))
                ->middlewares(config('raptor.controllers.inspirations.create.middlewares', ['auth', 'verified'])),
            'edit' => Edit::route('/inspirations/{record}/edit')
                ->label(config('raptor.controllers.inspirations.edit.label', __('Editar Inspiration')))
                ->name(config('raptor.controllers.inspirations.edit.name', 'inspirations.edit'))
                ->middlewares(config('raptor.controllers.inspirations.edit.middlewares', ['auth', 'verified'])),
            'execute' => Execute::route('/inspirations/execute/actions')
                ->label(config('raptor.controllers.inspirations.execute.label', __('Executar Inspiration')))
                ->name(config('raptor.controllers.inspirations.execute.name', 'inspirations.execute'))
                ->middlewares(config('raptor.controllers.inspirations.execute.middlewares', ['auth', 'verified'])),
        ];
    }

    protected function infolist(InfoList $infoList): InfoList
    {
        $infoList->columns([
            \Callcocam\LaravelRaptor\Support\Info\Columns\Types\CardColumn::make('info')
                ->title('Informações')
                ->description('Dados básicos')
                ->collapsible(true, true)
                ->columns([
                    \Callcocam\LaravelRaptor\Support\Info\Columns\Types\TextColumn::make('author')
                        ->label('Autor')
                        ->icon('FolderTree'),

                    \Callcocam\LaravelRaptor\Support\Info\Columns\Types\TextColumn::make('message')
                        ->label('Mensagem')
                        ->icon('Hash'),
                ]),

            \Callcocam\LaravelRaptor\Support\Info\Columns\Types\DateColumn::make('created_at')
                ->label('Criado em')
                ->format('d/m/Y H:i'),

            \Callcocam\LaravelRaptor\Support\Info\Columns\Types\DateColumn::make('updated_at')
                ->label('Atualizado em')
                ->format('d/m/Y H:i'),
        ]);

        return $infoList;
    }

    protected function form(Form $form): Form
    {
        $form->columns([
            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('author')
                ->label('Autor')
                ->required()
                ->rules(['required', 'string', 'max:255'])
                ->placeholder('Digite o autor')
                ->columnSpanFull(),

            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextareaField::make('message')
                ->label('Mensagem')
                ->placeholder('Mensagem da inspiração')
                ->rows(4)
                ->columnSpanFull(),
        ]);

        return $form;
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        $table->columns([
            \Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn::make('author')
                ->label('Autor')
                ->searchable()
                ->sortable(),

            \Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn::make('message')
                ->label('Mensagem')
                ->searchable()
                ->sortable(),
        ]);

        $table->filters([
            \Callcocam\LaravelRaptor\Support\Table\Filters\TrashedFilter::make(),
        ]);

        $table->actions([
            \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make('inspirations.show'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make('inspirations.edit'),

            // Edição Rápida
            // \Callcocam\LaravelRaptor\Support\Actions\Types\ModalAction::make('update')
            //     ->label('Edição Rápida')
            //     ->slideoverRight()
            //     ->columns([
            //         \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('name')
            //             ->label('Nome')
            //             ->required()
            //             ->columnSpanFull(),
            //         \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('slug')
            //             ->label('Slug')
            //             ->required()
            //             ->columnSpanFull(),
            //         \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextareaField::make('description')
            //             ->label('Descrição')
            //             ->columnSpanFull(),
            //     ]),

            \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make('inspirations.restore'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make('inspirations.forceDelete'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make('inspirations.destroy'),
        ]);

        $table->bulkActions([
            // Bulk actions
        ]);

        $table->headerActions([
            \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make('inspirations.create'),
        ]);

        return $table;
    }

    protected function resourcePath(): ?string
    {
        return 'tenant';
    }
}
