<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
use Callcocam\LaravelRaptor\Services\TenantDatabaseManager;
use Callcocam\LaravelRaptor\Support\Actions\Types\LinkAction;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\CheckboxField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SectionField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SelectField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField;
use Callcocam\LaravelRaptor\Support\Form\Form;
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\CardColumn;
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\HasManyColumn;
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\StatusColumn as StatusColumnInfolist;
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\TextColumn as TextInfolist;
use Callcocam\LaravelRaptor\Support\Info\InfoList as InfoListBuilder; 
use Callcocam\LaravelRaptor\Support\Pages\Index;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\BooleanColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\DateColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder; 
use Illuminate\Http\Request;

class TenantController extends LandlordController
{
    protected ?string $previousTenantDatabase = null;
    protected ?string $previousTenantSlug = null;

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
            'index' => Index::route(config('raptor.controllers.tenants.index.route', '/tenants'))
                ->label(config('raptor.controllers.tenants.index.label', __('Inquilinos')))
                ->name(config('raptor.controllers.tenants.index.name', 'tenants.index'))
                ->icon(config('raptor.controllers.tenants.index.icon', 'Shield'))
                ->group(config('raptor.controllers.tenants.index.group', 'Segurança'))
                ->groupCollapsible(config('raptor.controllers.tenants.index.groupCollapsible', true))
                ->order(config('raptor.controllers.tenants.index.order', 15))
                ->resource(config('raptor.landlord.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class))
                ->middlewares(config('raptor.controllers.tenants.index.middlewares', ['auth', 'verified'])), 
        ];
    }

    protected function infoList(InfoListBuilder $infoList): InfoListBuilder
    {
        $infoList->columns([
            CardColumn::make('basic_info')
                ->title('Informações Básicas')
                ->description('Detalhes do Inquilino')
                ->columns([
                    TextInfolist::make('name')
                        ->label('Nome'),

                    TextInfolist::make('document')
                        ->label('Documento'),

                    TextInfolist::make('domain')
                        ->label('Domínio'),

                    TextInfolist::make('database')
                        ->label('Database'),

                    TextInfolist::make('prefix')
                        ->label('Prefixo'),
                ]),
            CardColumn::make('contact_info')
                ->title('Informações de Contato')
                ->description('Detalhes de Contato do Inquilino')
                ->columns([
                    TextInfolist::make('email')
                        ->label('E-mail'),

                    TextInfolist::make('phone')
                        ->label('Telefone'),
                ]),
            HasManyColumn::make('users')
                ->label('Usuários Relacionados')
                ->relationship('users')
                ->displayField('name')
                ->actions([
                    LinkAction::make('view')
                        ->actionAlink()
                        ->label('Login como')
                        ->url(function ($target) {
                            if ($target->tenant_id === null) {
                                return null;
                            }

                            return sprintf('//%s/login-as'.'?%s', $target->tenant->domain, http_build_query([
                                'token' => auth()->user()->id,
                            ]));
                        })
                        ->targetBlank()
                        ->icon('LogIn'),
                ]),

            StatusColumnInfolist::make('status')
                ->label('Status'),

        ]);

        return $infoList;
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
                ->editable()
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
            \Callcocam\LaravelRaptor\Support\Actions\Types\LinkAction::make('tenants.view')
                ->visible(fn ($record) => ! empty($record->domain))
                ->actionAlink()
                ->label('Ver Site')
                ->url(fn ($record) => 'http://'.$record->domain)
                ->targetBlank()
                ->icon('ExternalLink'),
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
                    $conn = config('raptor.database.landlord_connection_name', 'landlord');
                    $except = $record?->id ? ",{$record->id},id" : '';

                    return ['required', 'string', 'max:255', "unique:{$conn}.tenants,domain{$except}"];
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
            // Seção de Domínios
            // RepeaterField::make('domains')
            //     ->label('Lista de Domínios')
            //     ->relationship('domains')
            //     ->compact()
            //     ->fields([
            //         TextField::make('domain')
            //             ->label('Domínio')
            //             ->required()
            //             ->placeholder('exemplo.com.br')
            //             ->helpText('Digite o domínio completo (ex: empresa.com.br, app.empresa.com)')
            //             ->rules(['required', 'string', 'max:255'])
            //             ->columnSpan('9'),

            //         CheckboxField::make('is_primary')
            //             ->label('Principal')
            //             ->helpText('Domínio principal do tenant')
            //             ->default(false)
            //             ->columnSpan('3'),
            //     ])
            //     ->columnSpanFull(),

            SectionField::make('settings')
                ->label('Configurações (JSON)')
                ->nested()
                ->fields([
                    SectionField::make('theme')
                        ->label('Tema')
                        ->nested()
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

                    SectionField::make('limits')
                        ->label('Limites de Cadastro')
                        ->nested()
                        ->fields([
                            TextField::make('max_admins')
                                ->label('Máx. Editores')
                                ->placeholder('0 = sem limite')
                                ->columnSpan('3'),

                            TextField::make('max_users')
                                ->label('Máx. Usuários Executores')
                                ->placeholder('0 = sem limite')
                                ->columnSpan('3'),

                            TextField::make('max_clients')
                                ->label('Máx. Clientes')
                                ->placeholder('0 = sem limite')
                                ->columnSpan('3'),

                            TextField::make('max_stores')
                                ->label('Máx. Lojas')
                                ->placeholder('0 = sem limite')
                                ->columnSpan('3'),
                        ])
                        ->placeholder('Limites de cadastro por recurso (0 ou vazio = sem limite)')
                        ->columnSpanFull(),

                    SectionField::make('features')
                        ->label('Funcionalidades')
                        ->nested()
                        ->fields([
                            CheckboxField::make('single_session')
                                ->label('Login Único — derrubar sessão anterior ao fazer novo login')
                                ->default(false)
                                ->columnSpan('12'),
                            CheckboxField::make('use_workflow')
                                ->label('Usar Workflow — habilitar módulo de workflow (Raptor Flow) para este tenant')
                                ->default(false)
                                ->columnSpan('12'),
                        ])
                        ->placeholder('Controles de comportamento do tenant')
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

    protected function beforeUpdate(Request $request, string $id): void
    {
        $model = $this->model()::findOrFail($id);
        $this->previousTenantDatabase = $model->getAttribute('database');
        $this->previousTenantSlug = $model->getAttribute('slug');
    }

    protected function beforeDelete(string $id): void
    {
        $model = $this->model()::findOrFail($id);
        $database = $model->getAttribute('database');
        if (! empty($database)) {
            app(TenantDatabaseManager::class)->deleteTenantRecordFromTenantDatabase($model);
        }
    }

    protected function beforeForceDelete(string $id): void
    {
        $model = $this->model()::withTrashed()->findOrFail($id);
        $database = $model->getAttribute('database');
        $manager = app(TenantDatabaseManager::class);
        if (! empty($database) && $manager->isDedicatedTenantDatabase($database)) {
            $manager->dropDatabase($database);
        }
    }

    /**
     * Pastas de migrations a rodar quando o tenant tiver database dedicado (passado na hora).
     *
     * @return array<int, string>
     */
    protected function tenantMigrationPaths(): array
    {
        return [
            'database/migrations/',
            'database/migrations/tenant/',
        ];
    }

    protected function afterCreate(Request $request, $model): void
    {
        $database = $model->getAttribute('database');
        if (empty($database)) {
            return;
        }

        $manager = app(TenantDatabaseManager::class);
        if (! $manager->isDedicatedTenantDatabase($database)) {
            return;
        }

        // Cria o banco (se não existir), roda migrations e sincroniza o tenant com id canônico.
        $manager->ensureDatabaseAndRunMigrations(
            $database,
            $this->tenantMigrationPaths(),
            $model,
            true
        );
        $manager->syncTenantPermissions($model, $database);
        $manager->createTenantConfiguration($model);
    }

    protected function afterUpdate(Request $request, $model): void
    {
        $manager = app(TenantDatabaseManager::class);
        $previousDatabase = $this->previousTenantDatabase;
        $previousSlug = $this->previousTenantSlug;
        $this->previousTenantDatabase = null;
        $this->previousTenantSlug = null;

        $database = $model->getAttribute('database');
        if (! empty($database) && $manager->isDedicatedTenantDatabase($database)) {
            // Roda apenas as migrations pendentes no banco dedicado atual.
            $manager->ensureDatabaseAndRunMigrations($database, $this->tenantMigrationPaths());
            // Sincroniza metadados garantindo id canônico.
            $manager->syncTenantRecordToTenantDatabase($model, $database, true);
            $manager->syncTenantPermissions($model, $database);
            $manager->createTenantConfiguration($model);
        }

        $databaseChanged = $previousDatabase !== null && $previousDatabase !== $database;
        if ($databaseChanged && $manager->isDedicatedTenantDatabase($previousDatabase)) {
            $manager->deleteTenantRecordFromDatabase($model, $previousDatabase, $previousSlug);
        }
    }

    protected function afterDelete(string $id, $model): void
    {
        //
    }

    protected function afterRestore(string $id, $model): void
    {
        $database = $model->getAttribute('database');
        if (empty($database)) {
            return;
        }
        $manager = app(TenantDatabaseManager::class);
        if (! $manager->isDedicatedTenantDatabase($database)) {
            return;
        }

        $manager->ensureDatabaseAndRunMigrations($database, $this->tenantMigrationPaths());
        $manager->syncTenantRecordToTenantDatabase($model, $database, true);
    }
}
