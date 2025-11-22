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
