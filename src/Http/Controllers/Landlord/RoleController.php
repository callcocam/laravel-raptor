<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithRequests;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\CheckboxField;
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

class RoleController extends LandlordController
{
    use WithRequests;

    public function getPages(): array
    {
        return [
            'index' => Index::route(config('raptor.controllers.roles.index.route', '/roles'))
                ->label(config('raptor.controllers.roles.index.label', __('Roles')))
                ->name(config('raptor.controllers.roles.index.name', 'roles.index'))
                ->icon(config('raptor.controllers.roles.index.icon', 'Key'))
                ->group(config('raptor.controllers.roles.index.group', 'Segurança'))
                ->groupCollapsible(config('raptor.controllers.roles.index.groupCollapsible', true))
                ->order(config('raptor.controllers.roles.index.order', 10))
                ->middlewares(config('raptor.controllers.roles.index.middlewares', ['auth', 'verified'])),
            'create' => Create::route('/roles/create')
                ->label(config('raptor.controllers.roles.create.label', __('Criar Role')))
                ->name(config('raptor.controllers.roles.create.name', 'roles.create'))
                ->middlewares(config('raptor.controllers.roles.create.middlewares', ['auth', 'verified'])),
            'edit' => Edit::route('/roles/{record}/edit')
                ->label(config('raptor.controllers.roles.edit.label', __('Editar Role')))
                ->name(config('raptor.controllers.roles.edit.name', 'roles.edit'))
                ->middlewares(config('raptor.controllers.roles.edit.middlewares', ['auth', 'verified'])),
            'execute' => Execute::route('/roles/execute/actions')
                ->label(config('raptor.controllers.roles.execute.label', __('Executar Role')))
                ->name(config('raptor.controllers.roles.execute.name', 'roles.execute'))
                ->middlewares(config('raptor.controllers.roles.execute.middlewares', ['auth', 'verified'])),
        ];
    }

    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.shinobi.models.role', \Callcocam\LaravelRaptor\Models\Role::class);
    }

    protected function form(Form $form): Form
    {
        $form->columns([
            TextField::make('name', config('raptor.controllers.roles.form.name.label', __('Nome')))
                ->required()
                ->placeholder(config('raptor.controllers.roles.form.name.placeholder', 'Ex: Administrador'))
                ->helpText(config('raptor.controllers.roles.form.name.helpText', __('Nome único para identificar a role'))),

            TextField::make('slug', config('raptor.controllers.roles.form.slug.label', __('Slug')))
                ->required()
                ->placeholder(config('raptor.controllers.roles.form.slug.placeholder', 'Ex: admin'))
                ->helpText(config('raptor.controllers.roles.form.slug.helpText', __('Identificador único da role (sem espaços)'))),

            TextareaField::make('description', config('raptor.controllers.roles.form.description.label', __('Descrição')))
                ->rows(3)
                ->placeholder(config('raptor.controllers.roles.form.description.placeholder', __('Descreva as responsabilidades desta role')))
                ->helpText(config('raptor.controllers.roles.form.description.helpText', __('Descrição detalhada da role'))),

            CheckboxField::make('special', config('raptor.controllers.roles.form.special.label', __('Permissões Especiais')))
                ->helpText(config('raptor.controllers.roles.form.special.helpText', __('Marque se esta role deve ter permissões especiais de administrador'))),

            CheckboxField::make('permissions', config('raptor.controllers.roles.form.permissions.label', __('Permissões')))
                ->relationship('permissions', 'name')
                ->multiple()
                ->columns(2)
                ->searchable()
                ->showSelectAll(true)
                ->defaultUsing(fn ($request, $model) => $model ? $model->permissions->pluck('id')->toArray() : [])
                ->helpText(config('raptor.controllers.roles.form.permissions.helpText', __('Selecione as permissões associadas a esta role'))),
        ]);

        return $form;
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        return $table->columns([
            TextColumn::make('name', config('raptor.controllers.roles.table.name', 'Nome'))
                ->searchable()
                ->sortable(),

            TextColumn::make('slug', config('raptor.controllers.roles.table.slug', 'Slug'))
                ->searchable()
                ->sortable(),

            TextColumn::make('description', config('raptor.controllers.roles.table.description', 'Descrição'))
                ->searchable(),

            BooleanColumn::make('special', config('raptor.controllers.roles.table.special', 'Especial'))
                ->trueLabel('Sim')
                ->falseLabel('Não')
                ->trueColor('success')
                ->falseColor('secondary')
                ->sortable(),

            DateColumn::make('created_at', config('raptor.controllers.roles.table.created_at', 'Criado em'))
                ->format('d/m/Y H:i')
                ->sortable(),

            DateColumn::make('updated_at', config('raptor.controllers.roles.table.updated_at', 'Atualizado'))
                ->relative()
                ->sortable(),
        ])
            ->filters([
                \Callcocam\LaravelRaptor\Support\Table\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make(config('raptor.controllers.roles.actions.show', 'roles.show')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make(config('raptor.controllers.roles.actions.edit', 'roles.edit')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make(config('raptor.controllers.roles.actions.restore', 'roles.restore')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make(config('raptor.controllers.roles.actions.force_delete', 'roles.forceDelete')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make(config('raptor.controllers.roles.actions.destroy', 'roles.destroy')),
            ])->headerActions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make(config('raptor.controllers.roles.actions.create', 'roles.create')),
            ]);
    }

    protected function infolist(InfoListBuilder $infolist): InfoListBuilder
    {
        return $infolist->columns([
            TextInfolist::make('name', config('raptor.controllers.roles.infolist.name', 'Nome')),
            TextInfolist::make('slug', config('raptor.controllers.roles.infolist.slug', 'Slug')),
            TextInfolist::make('description', config('raptor.controllers.roles.infolist.description', 'Descrição')),
            TextInfolist::make('special', config('raptor.controllers.roles.infolist.special', 'Permissões Especiais'))
                ->value(fn ($value) => $value ? 'Sim' : 'Não'),
            TextInfolist::make('created_at', config('raptor.controllers.roles.infolist.created_at', 'Criado em'))
                ->value(fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-'),
            TextInfolist::make('updated_at', config('raptor.controllers.roles.infolist.updated_at', 'Atualizado em'))
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
