<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use App\Models\Client;
use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\CheckboxField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\RepeaterField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SectionField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SelectField;
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
        $table->columns([
            TextColumn::make('name')
                ->label('Nome')
                ->sortable()
                ->searchable(),

            TextColumn::make('domain')
                ->label('Domínio')
                ->sortable()
                ->searchable(),

            BooleanColumn::make('status')
                ->label('Ativo')
                ->sortable(),

            DateColumn::make('created_at')
                ->label('Criado em')
                ->sortable(),
        ]);

        $table->filters([
            \Callcocam\LaravelRaptor\Support\Table\Filters\SelectFilter::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Rascunho',
                    'published' => 'Publicado',
                ]),
            \Callcocam\LaravelRaptor\Support\Table\Filters\TrashedFilter::make(),
        ]);

        $table->actions([
            \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make('tenants.show'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make('tenants.edit'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make('tenants.restore'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make('tenants.forceDelete'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make('tenants.destroy'),
        ]);

        $table->bulkActions([
            //
        ]);

        $table->headerActions([
            \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make('tenants.create'),
        ]);

        return $table;
    }


    protected function form(Form $form): Form
    {
        $form->columns([
            TextField::make('name')
                ->label('Nome')
                ->required()
                ->rules(['required', 'string', 'max:255'])
                ->placeholder('Nome do inquilino')
                ->columnSpan('6'),

            TextField::make('slug')
                ->label('Slug')
                ->rules(['nullable', 'string', 'max:255'])
                ->placeholder('slug-do-inquilino')
                ->columnSpan('6'),

            TextField::make('domain')
                ->label('Domínio')
                ->required()
                ->rules(function ($record) {
                    return ['required', 'string', 'max:255', 'unique:tenants,domain' . ($record ? ",{$record->id}" : '')];
                })
                ->placeholder('exemplo.com')
                ->columnSpan('4'),

            TextField::make('database')
                ->label('Database')
                ->rules(['nullable', 'string', 'max:255'])
                ->placeholder('nome_do_banco')
                ->columnSpan('4'),

            TextField::make('prefix')
                ->label('Prefixo')
                ->rules(['nullable', 'string', 'max:255'])
                ->placeholder('prefixo_')
                ->columnSpan('4'),

            TextField::make('email')
                ->label('E-mail')
                ->rules(['nullable', 'email', 'max:255'])
                ->placeholder('contato@exemplo.com')
                ->columnSpan('6'),

            TextField::make('phone')
                ->label('Telefone')
                ->phone()
                ->rules(['nullable', 'string', 'max:255'])
                ->placeholder('(00) 00000-0000')
                ->columnSpan('6'),

            TextField::make('document')
                ->label('Documento')
                ->cnpj()
                ->rules(['nullable', 'string', 'max:255'])
                ->placeholder('CNPJ/CPF')
                ->columnSpan('6'),

            SelectField::make('status')
                ->label('Status')
                ->required()
                ->options([
                    'draft' => 'Rascunho',
                    'published' => 'Publicado',
                ])
                ->default('draft')
                ->columnSpan('6'),

            CheckboxField::make('is_primary')
                ->label('Inquilino Principal')
                ->default(false)
                ->columnSpan('12'),

            SectionField::make('settings')
                ->label('Configurações (JSON)')
                // ->collapsible(true) // Habilita accordion
                // ->defaultOpen(true) // Inicia aberto
                ->fields([
                    RepeaterField::make('clients')
                        ->label('Clientes')
                        ->compact()
                        ->fields([
                            SelectField::make('name')
                                ->label('Nome')
                                ->options(Client::all()->pluck('name', 'id')->toArray())
                                ->required()
                                ->columnSpanSeven(),
                            TextField::make('subdomain')
                                ->label('Subdomínio')
                                ->required()
                                ->columnSpanFive(),
                        ])
                        ->placeholder('Adicione recursos ao inquilino')
                        ->columnSpanFull(),
                    SectionField::make('theme')
                        ->label('Tema')
                        ->fields([
                            SelectField::make('color')
                                ->label('Cor do Tema')
                                ->options([
                                    'default' => 'Padrão',
                                    'blue' => 'Azul',
                                    'green' => 'Verde',
                                    'amber' => 'Âmbar',
                                    'rose' => 'Rosa',
                                    'purple' => 'Roxo',
                                    'orange' => 'Laranja',
                                    'teal' => 'Azul Turquesa',
                                    'red' => 'Vermelho',
                                    'yellow' => 'Amarelo',
                                    'violet' => 'Violeta',
                                ])
                                ->default('green')
                                ->columnSpan('3'),

                            SelectField::make('font')
                                ->label('Fonte')
                                ->options([
                                    'default' => 'Padrão (Geist)',
                                    'inter' => 'Inter',
                                    'noto-sans' => 'Noto Sans',
                                    'nunito-sans' => 'Nunito Sans',
                                    'figtree' => 'Figtree',
                                ])
                                ->default('inter')
                                ->columnSpan('3'),

                            SelectField::make('rounded')
                                ->label('Arredondamento')
                                ->options([
                                    'none' => 'Nenhum',
                                    'small' => 'Pequeno',
                                    'medium' => 'Médio',
                                    'large' => 'Grande',
                                    'full' => 'Completo',
                                ])
                                ->default('small')
                                ->columnSpan('3'),

                            SelectField::make('variant')
                                ->label('Variante')
                                ->options([
                                    'default' => 'Padrão',
                                    'mono' => 'Monoespaçado',
                                    'scaled' => 'Escalado',
                                ])
                                ->default('default')
                                ->columnSpan('3'),
                        ])
                        ->placeholder('Configurações de tema')
                        ->columnSpanFull(),
                ])
                ->placeholder('Configurações gerais do inquilino')
                ->columnSpanFull(),
        ]);

        return $form;
    }

    /**
     * Define o resource path para as views
     */
    protected function resourcePath(): string
    {
        return 'landlord';
    }
}
