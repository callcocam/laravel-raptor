<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
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
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;

class TranslateController extends LandlordController
{
    /**
     * Define o model que será usado pelo controller
     */
    public function model(): ?string
    {
        return config('raptor.landlord.models.translate', \Callcocam\LaravelRaptor\Models\TranslationOverride::class);
    }

    /**
     * Define as páginas do recurso
     */
    public function getPages(): array
    {
        return [
            'index' => Index::route('/translates')
                ->label('Traduções')
                ->name('translates.index')
                ->icon('Languages')
                ->group('Sistema')
                ->groupCollapsible(true)
                ->order(30)
                ->middlewares(['auth', 'verified']),
            'create' => Create::route('/translates/create')
                ->label('Criar Tradução')
                ->name('translates.create')
                ->middlewares(['auth', 'verified']),
            'edit' => Edit::route('/translates/{record}/edit')
                ->label('Editar Tradução')
                ->name('translates.edit')
                ->middlewares(['auth', 'verified']),
            'execute' => Execute::route('/translates/execute/actions')
                ->label('Executar Ações')
                ->name('translates.execute')
                ->middlewares(['auth', 'verified']),
        ];
    }

    /**
     * Define a tabela de listagem
     */
    protected function table(TableBuilder $table): TableBuilder
    {
        $table->columns([
            TextColumn::make('tenant.name')
                ->label('Tenant')
                ->default('Global')
                ->badge()
                ->color(fn($value) => $value === 'Global' ? 'gray' : 'success')
                ->sortable()
                ->searchable(),

            TextColumn::make('group')
                ->label('Grupo')
                ->sortable()
                ->searchable()
                ->default('—'),

            TextColumn::make('key')
                ->label('Chave')
                ->sortable()
                ->searchable(),

            TextColumn::make('locale')
                ->label('Idioma')
                ->badge()
                ->color(fn($value) => match($value) {
                    'pt_BR' => 'primary',
                    'en' => 'success',
                    'es' => 'warning',
                    'fr' => 'info',
                    default => 'secondary',
                })
                ->sortable(),

            TextColumn::make('value')
                ->label('Tradução')
                ->limit(50)
                ->searchable(),
        ]);

        $table->filters([
            \Callcocam\LaravelRaptor\Support\Table\Filters\SelectFilter::make('locale')
                ->label('Idioma')
                ->options([
                    'pt_BR' => 'Português (BR)',
                    'en' => 'English',
                    'es' => 'Español',
                    'fr' => 'Français',
                ]),

            \Callcocam\LaravelRaptor\Support\Table\Filters\SelectFilter::make('group')
                ->label('Grupo')
                ->options($this->model()::query()
                    ->select('group')
                    ->distinct()
                    ->whereNotNull('group')
                    ->pluck('group', 'group')
                    ->toArray()),

            \Callcocam\LaravelRaptor\Support\Table\Filters\TernaryFilter::make('is_global')
                ->label('Tipo')
                ->placeholder('Todos')
                ->trueLabel('Globais')
                ->falseLabel('Por Tenant')
                ->queries(
                    true: fn($query) => $query->whereNull('tenant_id'),
                    false: fn($query) => $query->whereNotNull('tenant_id'),
                ),
        ]);

        $table->actions([
            \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make('translates.edit'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make('translates.destroy'),
        ]);

        $table->bulkActions([
            // \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteBulkAction::make('translates.bulkDestroy'),
        ]);

        $table->headerActions([
            \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make('translates.create'),
        ]);

        return $table;
    }

    /**
     * Define o formulário de criação/edição
     */
    protected function form(Form $form): Form
    {
        $availableLocales = config('raptor.translation.available_locales', ['pt_BR', 'en', 'es', 'fr']);
        $localeOptions = [
            'pt_BR' => 'Português (BR)',
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
        ];

        $tenantOptions = \Callcocam\LaravelRaptor\Models\Tenant::query()
            ->pluck('name', 'id')
            ->toArray();

        $form->columns([
            SelectField::make('tenant_id')
                ->label('Tenant')
                ->helperText('Deixe vazio para criar tradução global do sistema')
                ->options($tenantOptions)
                ->searchable()
                ->nullable()
                ->columnSpanFull(),

            TextField::make('group')
                ->label('Grupo')
                ->helperText('Ex: products, cart, checkout (opcional)')
                ->placeholder('Deixe vazio se não houver grupo')
                ->columnSpanFull(),

            TextField::make('key')
                ->label('Chave')
                ->helperText('Ex: product, add_to_cart, title')
                ->required()
                ->columnSpanFull(),

            SelectField::make('locale')
                ->label('Idioma')
                ->options(array_intersect_key($localeOptions, array_flip($availableLocales)))
                ->required()
                ->default(config('raptor.translation.default_locale', 'pt_BR'))
                ->columnSpanFull(),

            TextareaField::make('value')
                ->label('Tradução')
                ->helperText('Valor traduzido que substituirá a tradução padrão')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
        ]);

        return $form;
    }

    /**
     * Define a infolist de visualização
     */
    protected function infoList($record): InfoListBuilder
    {
        $infoList = new InfoListBuilder($this->model(), $record);

        $infoList->columns([
            TextInfolist::make('tenant.name')
                ->label('Tenant')
                ->default('Global (Sistema)'),

            TextInfolist::make('group')
                ->label('Grupo')
                ->default('—'),

            TextInfolist::make('key')
                ->label('Chave'),

            TextInfolist::make('locale')
                ->label('Idioma'),

            TextInfolist::make('value')
                ->label('Tradução'),

            TextInfolist::make('full_key')
                ->label('Chave Completa')
                ->value(fn($record) => $record->getFullKey()),

            TextInfolist::make('created_at')
                ->label('Criado em')
                ->dateTime(),

            TextInfolist::make('updated_at')
                ->label('Atualizado em')
                ->dateTime(),
        ]);

        return $infoList;
    }

    /**
     * Define o resource path para as views
     */
    protected function resourcePath(): ?string
    {
        return 'landlord';
    }
}
