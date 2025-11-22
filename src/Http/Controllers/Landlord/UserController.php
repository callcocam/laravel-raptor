<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithRequests;
use Callcocam\LaravelRaptor\Support\Pages\Create;
use Callcocam\LaravelRaptor\Support\Pages\Edit;
use Callcocam\LaravelRaptor\Support\Pages\Execute;
use Callcocam\LaravelRaptor\Support\Pages\Index;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;

class UserController extends LandlordController
{
    use WithRequests;


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

    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.landlord.models.user', \Callcocam\LaravelRaptor\Models\Auth\User::class);
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        return $table;
    }

    /**
     * Define o resource path para as views
     */
    protected function resourcePath(): string
    {
        return 'landlord';
    }
}
