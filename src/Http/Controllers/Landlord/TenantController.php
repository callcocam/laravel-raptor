<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
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

class TenantController extends LandlordController
{
    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.landlord.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
    }


    public function getPages(): array
    {
        return [
            'index' => Index::route('/tenants')
                ->label('Inquilinos')
                ->name('tenants.index')
                ->icon('Shield')
                ->group('Segurança')
                ->groupCollapsible(true)
                ->order(15)
                ->middlewares(['auth', 'verified']),
            'create' => Create::route('/tenants/create')
                ->label('Criar Inquilino')
                ->name('tenants.create')
                ->middlewares(['auth', 'verified']),
            'edit' => Edit::route('/tenants/{record}/edit')
                ->label('Editar Inquilinos')
                ->name('tenants.edit')
                ->middlewares(['auth', 'verified']),
            'execute' => Execute::route('/tenants/execute/actions')
                ->label('Executar Inquilinos')
                ->name('tenants.execute')
                ->middlewares(['auth', 'verified']),
        ];
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
