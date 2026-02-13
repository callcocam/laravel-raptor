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
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\ComboboxField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\EmailField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\PasswordField;
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
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\EmailColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;

class UserController extends LandlordController
{
    use WithRequests;

    public function getPages(): array
    {
        return [
            'index' => Index::route('/users')
                ->label(config('raptor.controllers.users.index.label', __('Usuários')))
                ->name(config('raptor.controllers.users.index.name', 'users.index'))
                ->icon(config('raptor.controllers.users.index.icon', 'Users'))
                ->group(config('raptor.controllers.users.index.group', 'Segurança'))
                ->groupCollapsible(config('raptor.controllers.users.index.groupCollapsible', true))
                ->order(config('raptor.controllers.users.index.order', 5))
                ->middlewares(config('raptor.controllers.users.index.middlewares', ['auth', 'verified'])),
            'create' => Create::route('/users/create')
                ->label(config('raptor.controllers.users.create.label', __('Criar Usuário')))
                ->name(config('raptor.controllers.users.create.name', 'users.create'))
                ->icon(config('raptor.controllers.users.create.icon', 'Users'))
                ->group(config('raptor.controllers.users.create.group', 'Segurança'))
                ->groupCollapsible(config('raptor.controllers.users.create.groupCollapsible', true))
                ->order(config('raptor.controllers.users.create.order', 5))
                ->middlewares(config('raptor.controllers.users.create.middlewares', ['auth', 'verified'])),
            'edit' => Edit::route('/users/{record}/edit')
                ->label(config('raptor.controllers.users.edit.label', __('Editar Usuário')))
                ->name(config('raptor.controllers.users.edit.name', 'users.edit'))
                ->icon(config('raptor.controllers.users.edit.icon', 'Users'))
                ->group(config('raptor.controllers.users.edit.group', 'Segurança'))
                ->groupCollapsible(config('raptor.controllers.users.edit.groupCollapsible', true))
                ->order(config('raptor.controllers.users.edit.order', 5))
                ->middlewares(config('raptor.controllers.users.edit.middlewares', ['auth', 'verified'])),
            'execute' => Execute::route('/users/execute/actions')
                ->label(config('raptor.controllers.users.execute.label', __('Executar Usuário')))
                ->name(config('raptor.controllers.users.execute.name', 'users.execute'))
                ->icon(config('raptor.controllers.users.execute.icon', 'Users'))
                ->group(config('raptor.controllers.users.execute.group', 'Segurança'))
                ->groupCollapsible(config('raptor.controllers.users.execute.groupCollapsible', true))
                ->order(config('raptor.controllers.users.execute.order', 5))
                ->middlewares(config('raptor.controllers.users.execute.middlewares', ['auth', 'verified'])),
        ];
    }

    /**
     * Define o model que será usado pelo controller (landlord)
     */
    public function model(): ?string
    {
        return config('raptor.landlord.models.user', \App\Models\User::class);
    }

    protected function form(Form $form): Form
    {
        $form->columns([
            ComboboxField::make(
                'tenant_id',
                config('raptor.controllers.users.form.tenant_id.label', __('Tenant'))
            )
                ->relationship('tenant', 'name')
                ->placeholder(config('raptor.controllers.users.form.tenant_id.placeholder', __('Selecione o tenant')))
                ->helpText(config('raptor.controllers.users.form.tenant_id.helpText', __('Atribua o usuário a um tenant'))),
            TextField::make('name', config('raptor.controllers.users.form.name.label', __('Nome')))
                ->required()
                ->columnSpan('7')
                ->placeholder(config('raptor.controllers.users.form.name.placeholder', __('Nome completo do usuário')))
                ->helpText(config('raptor.controllers.users.form.name.helpText', __('Nome completo do usuário'))),

            EmailField::make('email', config('raptor.controllers.users.form.email.label', __('E-mail')))
                ->required()
                ->columnSpan('5')
                ->rules(function ($record) {
                    return ['required', 'string', 'max:255', 'unique:users,email'.($record ? ",{$record->id}" : '')];
                })
                ->placeholder(config('raptor.controllers.users.form.email.placeholder', __('email@exemplo.com')))
                ->helpText(config('raptor.controllers.users.form.email.helpText', __('E-mail único para login'))),

            PasswordField::make('password', config('raptor.controllers.users.form.password.label', __('Senha')))
                ->required()
                ->minLength(8)
                ->showToggle()
                ->columnSpan('6')
                ->helpText(config('raptor.controllers.users.form.password.helpText', __('Senha com no mínimo 8 caracteres'))),

            PasswordField::make('password_confirmation', config('raptor.controllers.users.form.password_confirmation.label', __('Confirmar Senha')))
                ->required()
                ->minLength(8)
                ->showToggle()
                ->columnSpan('6')
                ->helpText(config('raptor.controllers.users.form.password_confirmation.helpText', __('Digite a senha novamente'))),

            CheckboxField::make('roles', config('raptor.controllers.users.form.roles.label', __('Papéis')))
                ->relationship('roles', 'name')
                ->multiple()
                ->defaultUsing(fn ($request, $model) => $model ? $model->roles->pluck('id')->toArray() : [])
                ->helpText(config('raptor.controllers.users.form.roles.helpText', __('Atribua papéis ao usuário'))),
        ]);

        return $form;
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        return $table->columns([
            TextColumn::make('name', config('raptor.controllers.users.table.name', 'Nome'))
                ->searchable()
                ->sortable(),

            EmailColumn::make('email', config('raptor.controllers.users.table.email', 'E-mail'))
                ->searchable()
                ->sortable(),

            BooleanColumn::make('email_verified_at', config('raptor.controllers.users.table.email_verified_at', 'Verificado'))
                ->trueLabel('Sim')
                ->falseLabel('Não')
                ->trueColor('success')
                ->falseColor('warning')
                ->sortable(),

            DateColumn::make('created_at', config('raptor.controllers.users.table.created_at', 'Criado em'))
                ->format('d/m/Y H:i')
                ->sortable(),

            DateColumn::make('updated_at', config('raptor.controllers.users.table.updated_at', 'Atualizado'))
                ->relative()
                ->sortable(),
        ])
            ->filters([
                \Callcocam\LaravelRaptor\Support\Table\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make(config('raptor.controllers.users.actions.show', 'users.show')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make(config('raptor.controllers.users.actions.edit', 'users.edit')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make(config('raptor.controllers.users.actions.restore', 'users.restore')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make(config('raptor.controllers.users.actions.force_delete', 'users.forceDelete')),
                \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make(config('raptor.controllers.users.actions.destroy', 'users.destroy')),
            ])->headerActions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make(config('raptor.controllers.users.actions.create', 'users.create')),
            ]);
    }

    protected function infolist(InfoListBuilder $infolist): InfoListBuilder
    {
        return $infolist->columns([
            TextInfolist::make('name', config('raptor.controllers.users.infolist.name', 'Nome')),
            TextInfolist::make('email', config('raptor.controllers.users.infolist.email', 'E-mail')),
            TextInfolist::make('email_verified_at', config('raptor.controllers.users.infolist.email_verified_at', 'E-mail Verificado'))
                ->castFormat(fn ($value) => $value ? 'Sim - '.\Carbon\Carbon::parse($value)->format('d/m/Y H:i') : 'Não'),
            TextInfolist::make('created_at', config('raptor.controllers.users.infolist.created_at', 'Criado em'))
                ->castFormat(fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-'),
            TextInfolist::make('updated_at', config('raptor.controllers.users.infolist.updated_at', 'Atualizado em'))
                ->castFormat(fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-'),
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
