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
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextareaField;
use Callcocam\LaravelRaptor\Support\Form\Form;
use Callcocam\LaravelRaptor\Support\Info\InfoList as InfoListBuilder;
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\TextColumn as TextInfolist;
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
            'index' => Index::route('/permissions')
                ->label('Permissões')
                ->name('permissions.index')
                ->icon('Shield')
                ->group('Segurança')
                ->groupCollapsible(true)
                ->order(15)
                ->middlewares(['auth', 'verified']),
            'create' => Create::route('/permissions/create')
                ->label('Criar Permissão')
                ->name('permissions.create')
                ->middlewares(['auth', 'verified']),
            'edit' => Edit::route('/permissions/{record}/edit')
                ->label('Editar Permissão')
                ->name('permissions.edit')
                ->middlewares(['auth', 'verified']),
            'execute' => Execute::route('/permissions/execute/actions')
                ->label('Executar Permissão')
                ->name('permissions.execute')
                ->middlewares(['auth', 'verified']),
        ];
    }
    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.shinobi.models.permission', \Callcocam\LaravelRaptor\Support\Shinobi\Models\Permission::class);
    }



    protected function form(Form $form): Form
    {
        $form->columns([
            TextField::make('name', 'Nome')
                ->required()
                ->placeholder('Ex: Editar Usuários')
                ->helpText('Nome descritivo da permissão'),

            TextField::make('slug', 'Slug')
                ->required()
                ->placeholder('Ex: users.edit')
                ->helpText('Identificador único da permissão (formato: recurso.acao)'),

            TextareaField::make('description', 'Descrição')
                ->rows(3)
                ->placeholder('Descreva o que esta permissão permite fazer')
                ->helpText('Descrição detalhada da permissão'),

            SelectField::make('resource', 'Recurso')
                ->options([
                    'users' => 'Usuários',
                    'roles' => 'Roles',
                    'permissions' => 'Permissões',
                    'posts' => 'Posts',
                    'pages' => 'Páginas',
                    'settings' => 'Configurações',
                ])
                ->placeholder('Selecione o recurso')
                ->helpText('Recurso ao qual esta permissão se aplica'),
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

            TextColumn::make('resource', 'Recurso')
                ->searchable()
                ->sortable(),

            TextColumn::make('description', 'Descrição')
                ->searchable(),

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
                \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make('permissions.show'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make('permissions.edit'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make('permissions.restore'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make('permissions.forceDelete'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make('permissions.destroy'),
            ])->headerActions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make('permissions.create'),
            ]);
    }

    protected function infolist(InfoListBuilder $infolist): InfoListBuilder
    {
        return $infolist->columns([
            TextInfolist::make('name', 'Nome'),
            TextInfolist::make('slug', 'Slug'),
            TextInfolist::make('resource', 'Recurso'),
            TextInfolist::make('description', 'Descrição'),
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
