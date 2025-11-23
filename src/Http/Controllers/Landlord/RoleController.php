<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
use Callcocam\LaravelRaptor\Models\Permission;
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
            'index' => Index::route('/roles')
                ->label('Roles')
                ->name('roles.index')
                ->icon('Key')
                ->group('Segurança')
                ->groupCollapsible(true)
                ->order(10)
                ->middlewares(['auth', 'verified']),
            'create' => Create::route('/roles/create')
                ->label('Criar Role')
                ->name('roles.create')
                ->middlewares(['auth', 'verified']),
            'edit' => Edit::route('/roles/{record}/edit')
                ->label('Editar Role')
                ->name('roles.edit')
                ->middlewares(['auth', 'verified']),
            'execute' => Execute::route('/roles/execute/actions')
                ->label('Executar Role')
                ->name('roles.execute')
                ->middlewares(['auth', 'verified']),
        ];
    }

    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.shinobi.models.role', \Callcocam\LaravelRaptor\Support\Shinobi\Models\Role::class);
    }

    protected function form(Form $form): Form
    {
        $form->columns([
            TextField::make('name', 'Nome')
                ->required()
                ->placeholder('Ex: Administrador')
                ->helpText('Nome único para identificar a role'),

            TextField::make('slug', 'Slug')
                ->required()
                ->placeholder('Ex: admin')
                ->helpText('Identificador único da role (sem espaços)'),

            TextareaField::make('description', 'Descrição')
                ->rows(3)
                ->placeholder('Descreva as responsabilidades desta role')
                ->helpText('Descrição detalhada da role'),

            CheckboxField::make('special', 'Permissões Especiais')
                ->helpText('Marque se esta role deve ter permissões especiais de administrador'),

            CheckboxField::make('permissions', 'Permissões')
                ->multiple()
                ->options(Permission::all()->pluck('name', 'id')->toArray())
                ->columns(2)
                ->searchable()
                ->showSelectAll(true)
                ->helpText('Selecione as permissões associadas a esta role'),
        ]);

        return $form;
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        return $table->columns([
            TextColumn::make('name', 'Nome')
                ->searchable()
                ->sortable(),

            TextColumn::make('slug', 'Slug')
                ->searchable()
                ->sortable(),

            TextColumn::make('description', 'Descrição')
                ->searchable(),

            BooleanColumn::make('special', 'Especial')
                ->trueLabel('Sim')
                ->falseLabel('Não')
                ->trueColor('success')
                ->falseColor('secondary')
                ->sortable(),

            DateColumn::make('created_at', 'Criado em')
                ->format('d/m/Y H:i')
                ->sortable(),

            DateColumn::make('updated_at', 'Atualizado')
                ->relative()
                ->sortable(),
        ])
            ->filters([
                \Callcocam\LaravelRaptor\Support\Table\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Rascunho',
                        'published' => 'Publicado',
                    ]),
                \Callcocam\LaravelRaptor\Support\Table\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make('roles.show'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make('roles.edit'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make('roles.restore'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make('roles.forceDelete'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make('roles.destroy'),
            ])->headerActions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make('roles.create'),
            ]);
    }

    protected function infolist(InfoListBuilder $infolist): InfoListBuilder
    {
        return $infolist->columns([
            TextInfolist::make('name', 'Nome'),
            TextInfolist::make('slug', 'Slug'),
            TextInfolist::make('description', 'Descrição'),
            TextInfolist::make('special', 'Permissões Especiais')
                ->value(fn($value) => $value ? 'Sim' : 'Não'),
            TextInfolist::make('created_at', 'Criado em')
                ->value(fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-'),
            TextInfolist::make('updated_at', 'Atualizado em')
                ->value(fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-'),
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
