<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Tenant;

use Callcocam\LaravelRaptor\Http\Controllers\TenantController;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithRequests;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\CheckboxField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\EmailField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\PasswordField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField;
use Callcocam\LaravelRaptor\Support\Form\Form;
use Callcocam\LaravelRaptor\Support\Info\InfoList as InfoListBuilder;
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\TextColumn as TextInfolist;
use Callcocam\LaravelRaptor\Support\Pages\Create;
use Callcocam\LaravelRaptor\Support\Pages\Edit;
use Callcocam\LaravelRaptor\Support\Pages\Execute;
use Callcocam\LaravelRaptor\Support\Pages\Index; 
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\BooleanColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\DateColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\EmailColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;

class UserController extends TenantController
{
    use WithRequests;

    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.landlord.models.user', \Callcocam\LaravelRaptor\Models\Auth\User::class);
    }

    public function getPages(): array
    {
        return [
            'index' => Index::route('/users')
                ->label('Usuários')
                ->name('users.index')
                ->icon('Users')
                ->group('Segurança')
                ->groupCollapsible(true)
                ->order(5)
                ->middlewares(['auth', 'verified']),
            'create' => Create::route('/users/create')
                ->label('Criar Usuário')
                ->name('users.create')
                ->middlewares(['auth', 'verified']),
            'edit' => Edit::route('/users/{record}/edit')
                ->label('Editar Usuário')
                ->name('users.edit')
                ->middlewares(['auth', 'verified']),
            'execute' => Execute::route('/users/execute/actions')
                ->label('Executar Usuário')
                ->name('users.execute')
                ->middlewares(['auth', 'verified']),
        ];
    }

    protected function form(Form $form): Form
    {
        $form->columns([
            TextField::make('name', 'Nome')
                ->required()
                ->placeholder('Nome completo do usuário')
                ->helpText('Nome completo do usuário'),

            EmailField::make('email', 'E-mail')
                ->required()
                ->placeholder('email@exemplo.com')
                ->helpText('E-mail único para login'),

            PasswordField::make('password', 'Senha')
                ->required()
                ->minLength(8)
                ->showToggle()
                ->helpText('Senha com no mínimo 8 caracteres'),

            PasswordField::make('password_confirmation', 'Confirmar Senha')
                ->required()
                ->minLength(8)
                ->showToggle()
                ->helpText('Digite a senha novamente'),

            CheckboxField::make('email_verified_at', 'E-mail Verificado')
                ->helpText('Marque se o e-mail já foi verificado'),
        ]);

        return $form;
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        return $table->columns([
            TextColumn::make('name', 'Nome')
                ->searchable()
                ->sortable(),

            EmailColumn::make('email', 'E-mail')
                ->searchable()
                ->sortable(),

            BooleanColumn::make('email_verified_at', 'Verificado')
                ->trueLabel('Sim')
                ->falseLabel('Não')
                ->trueColor('success')
                ->falseColor('warning')
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
                \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make('users.show'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make('users.edit'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make('users.restore'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make('users.forceDelete'),
                \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make('users.destroy'),
            ])->headerActions([
                \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make('users.create'),
            ]);
    }

    protected function infolist(InfoListBuilder $infolist): InfoListBuilder
    {
        return $infolist->columns([
            TextInfolist::make('name', 'Nome'),
            TextInfolist::make('email', 'E-mail'),
            TextInfolist::make('email_verified_at', 'E-mail Verificado')
                ->value(fn($value) => $value ? 'Sim - ' . \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : 'Não'),
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
        return 'tenant';
    }
}
