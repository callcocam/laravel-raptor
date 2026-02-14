<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithRequests;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextareaField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField;
use Callcocam\LaravelRaptor\Support\Form\Form;
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\TextColumn as TextInfolist;
use Callcocam\LaravelRaptor\Support\Info\InfoList as InfoListBuilder;
use Callcocam\LaravelRaptor\Support\Pages\Create;
use Callcocam\LaravelRaptor\Support\Pages\Edit;
use Callcocam\LaravelRaptor\Support\Pages\Execute;
use Callcocam\LaravelRaptor\Support\Pages\Index;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\BooleanColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\DateColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;

class PermissionController extends LandlordController
{
    use WithRequests;

    public function getPages(): array
    {
        return [
            'index' => Index::route(config('raptor.controllers.permissions.index.route', '/permissions'))
                ->label(config('raptor.controllers.permissions.index.label', __('Permissões')))
                ->name(config('raptor.controllers.permissions.index.name', 'permissions.index'))
                ->icon(config('raptor.controllers.permissions.index.icon', 'Shield'))
                ->group(config('raptor.controllers.permissions.index.group', 'Segurança'))
                ->groupCollapsible(config('raptor.controllers.permissions.index.groupCollapsible', true))
                ->order(config('raptor.controllers.permissions.index.order', 15))
                ->middlewares(config('raptor.controllers.permissions.index.middlewares', ['auth', 'verified'])),
            'create' => Create::route('/permissions/create')
                ->label(config('raptor.controllers.permissions.create.label', __('Criar Permissão')))
                ->name(config('raptor.controllers.permissions.create.name', 'permissions.create'))
                ->icon(config('raptor.controllers.permissions.create.icon', 'ShieldPlus'))
                ->middlewares(config('raptor.controllers.permissions.create.middlewares', ['auth', 'verified'])),
            'edit' => Edit::route('/permissions/{record}/edit')
                ->label(config('raptor.controllers.permissions.edit.label', __('Editar Permissão')))
                ->name(config('raptor.controllers.permissions.edit.name', 'permissions.edit'))
                ->icon(config('raptor.controllers.permissions.edit.icon', 'ShieldEdit'))
                ->middlewares(config('raptor.controllers.permissions.edit.middlewares', ['auth', 'verified'])),
            'execute' => Execute::route('/permissions/execute/actions')
                ->label(config('raptor.controllers.permissions.execute.label', __('Executar Permissão')))
                ->name(config('raptor.controllers.permissions.execute.name', 'permissions.execute'))
                ->icon(config('raptor.controllers.permissions.execute.icon', 'ShieldExecute'))
                ->middlewares(config('raptor.controllers.permissions.execute.middlewares', ['auth', 'verified'])),
        ];
    }

    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.shinobi.models.permission', \Callcocam\LaravelRaptor\Models\Permission::class);
    }

    protected function form(Form $form): Form
    {
        $form->columns([
            TextField::make('name', config('raptor.controllers.permissions.form.name.label', __('Nome')))
                ->required()
                ->placeholder(config('raptor.controllers.permissions.form.name.placeholder', 'Ex: Editar Usuários'))
                ->helpText(config('raptor.controllers.permissions.form.name.helpText', __('Nome descritivo da permissão')))
                ->columnSpanFull(),

            TextField::make('slug', config('raptor.controllers.permissions.form.slug.label', __('Slug')))
                ->required()
                ->placeholder(config('raptor.controllers.permissions.form.slug.placeholder', 'Ex: users.edit'))
                ->helpText(config('raptor.controllers.permissions.form.slug.helpText', __('Identificador único da permissão (formato: recurso.acao)')))
                ->columnSpanFull(),

            TextareaField::make('description', config('raptor.controllers.permissions.form.description.label', __('Descrição')))
                ->rows(3)
                ->placeholder(config('raptor.controllers.permissions.form.description.placeholder', __('Descreva o que esta permissão permite fazer')))
                ->helpText(config('raptor.controllers.permissions.form.description.helpText', __('Descrição detalhada da permissão')))
                ->columnSpanFull(),
        ]);

        return $form;
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        return $table->columns([
            TextColumn::make('name', config('raptor.controllers.permissions.table.name', 'Nome'))
                ->searchable()
                ->sortable(),

            TextColumn::make('slug', config('raptor.controllers.permissions.table.slug', 'Slug'))
                ->searchable()
                ->sortable(),

            BooleanColumn::make('status', config('raptor.controllers.permissions.table.status', 'Status'))
                ->editable()
                ->executeUrl(route('landlord.permissions.execute'))
                ->sortable()->columnSpanFull(),

            DateColumn::make('created_at', config('raptor.controllers.permissions.table.created_at', 'Criado em'))
                ->format('d/m/Y H:i')
                ->sortable(),

            DateColumn::make('updated_at', config('raptor.controllers.permissions.table.updated_at', 'Atualizado'))
                ->relative()
                ->sortable(),

            TextColumn::make('description', config('raptor.controllers.permissions.table.description', 'Descrição'))
                ->searchable()->columnSpanFull(),
        ])
            ->filters([
                \Callcocam\LaravelRaptor\Support\Table\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make(config('raptor.controllers.permissions.actions.show', 'permissions.show')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make(config('raptor.controllers.permissions.actions.edit', 'permissions.edit')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make(config('raptor.controllers.permissions.actions.restore', 'permissions.restore')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make(config('raptor.controllers.permissions.actions.force_delete', 'permissions.forceDelete')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make(config('raptor.controllers.permissions.actions.destroy', 'permissions.destroy')),
            ])->headerActions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make(config('raptor.controllers.permissions.actions.create', 'permissions.create')),
            ]);
    }

    protected function infolist(InfoListBuilder $infolist): InfoListBuilder
    {
        return $infolist->columns([
            TextInfolist::make('name', config('raptor.controllers.permissions.infolist.name', 'Nome')),
            TextInfolist::make('slug', config('raptor.controllers.permissions.infolist.slug', 'Slug')),
            TextInfolist::make('description', config('raptor.controllers.permissions.infolist.description', 'Descrição')),
            TextInfolist::make('created_at', config('raptor.controllers.permissions.infolist.created_at', 'Criado em'))
                ->value(fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-'),
            TextInfolist::make('updated_at', config('raptor.controllers.permissions.infolist.updated_at', 'Atualizado em'))
                ->value(fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-'),
        ]);
    }

    /**
     * Define o resource path para as views
     */
    protected function resourcePath(): string
    {
        return 'landlord';
    }
}
