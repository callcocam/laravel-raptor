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
