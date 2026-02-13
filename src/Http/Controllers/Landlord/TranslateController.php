<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\RepeaterField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SelectField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField;
use Callcocam\LaravelRaptor\Support\Form\Form;
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\TextColumn as TextInfolist;
use Callcocam\LaravelRaptor\Support\Info\InfoList as InfoListBuilder;
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
        return config('raptor.landlord.models.translation_group', \Callcocam\LaravelRaptor\Models\TranslationGroup::class);
    }

    /**
     * Define as páginas do recurso
     */
    public function getPages(): array
    {
        return [
            'index' => Index::route(config('raptor.controllers.translates.index.route', '/translates'))
                ->label(config('raptor.controllers.translates.index.label', __('Traduções')))
                ->name(config('raptor.controllers.translates.index.name', 'translates.index'))
                ->icon(config('raptor.controllers.translates.index.icon', 'Languages'))
                ->group(config('raptor.controllers.translates.index.group', 'Sistema'))
                ->groupCollapsible(config('raptor.controllers.translates.index.groupCollapsible', true))
                ->order(config('raptor.controllers.translates.index.order', 30))
                ->middlewares(config('raptor.controllers.translates.index.middlewares', ['auth', 'verified'])),
            'create' => Create::route('/translates/create')
                ->label(config('raptor.controllers.translates.create.label', __('Criar Tradução')))
                ->name(config('raptor.controllers.translates.create.name', 'translates.create'))
                ->middlewares(config('raptor.controllers.translates.create.middlewares', ['auth', 'verified'])),
            'edit' => Edit::route('/translates/{record}/edit')
                ->label(config('raptor.controllers.translates.edit.label', __('Editar Tradução')))
                ->name(config('raptor.controllers.translates.edit.name', 'translates.edit'))
                ->middlewares(config('raptor.controllers.translates.edit.middlewares', ['auth', 'verified'])),
            'execute' => Execute::route('/translates/execute/actions')
                ->label(config('raptor.controllers.translates.execute.label', __('Executar Ações')))
                ->name(config('raptor.controllers.translates.execute.name', 'translates.execute'))
                ->middlewares(config('raptor.controllers.translates.execute.middlewares', ['auth', 'verified'])),
        ];
    }

    /**
     * Define a tabela de listagem
     */
    protected function table(TableBuilder $table): TableBuilder
    {
        // Eager load o relacionamento group para evitar N+1
        // $table->query(function ($query) {
        //     return $query->with('group.tenant');
        // });

        $table->columns([
            TextColumn::make('tenant.name')
                ->label('Tenant')
                ->default('Global')
                ->badge()
                ->color(fn ($value) => $value === 'Global' ? 'gray' : 'success')
                ->sortable()
                ->searchable(),

            TextColumn::make('name')
                ->label('Grupo')
                ->sortable()
                ->searchable()
                ->default('—'),

            TextColumn::make('locale')
                ->label('Idioma')
                ->badge()
                ->color(fn ($value) => match ($value) {
                    'pt_BR' => 'primary',
                    'en' => 'success',
                    'es' => 'warning',
                    'fr' => 'info',
                    default => 'secondary',
                })
                ->sortable(),
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

            \Callcocam\LaravelRaptor\Support\Table\Filters\TernaryFilter::make('is_global')
                ->label('Tipo')
                ->placeholder('Todos')
                ->trueLabel('Globais')
                ->falseLabel('Por Tenant')
                ->queries(
                    true: fn ($query) => $query->whereNull('tenant_id'),
                    false: fn ($query) => $query->whereNotNull('tenant_id'),
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

            // Action para gerar arquivos JSON
            \Callcocam\LaravelRaptor\Support\Actions\Types\ModalAction::make('translates.generate_json')
                ->label('Gerar JSON')
                ->icon('FileJson')
                ->color('success')
                ->url(route('landlord.translates.execute'))
                ->actionType('header')
                ->callback(function () {
                    $translationService = app(\Callcocam\LaravelRaptor\Services\TranslationService::class);

                    try {
                        // Gera arquivos para todos os locales globais
                        $translationService->generateAllJsonFiles(null);

                        return redirect()->back()->with('success', 'Arquivos JSON gerados com sucesso!');
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', 'Erro ao gerar arquivos JSON: '.$e->getMessage());
                    }
                }),

            // Action para sincronizar JSON com banco
            \Callcocam\LaravelRaptor\Support\Actions\Types\ModalAction::make('translates.sync_json')
                ->label('Sincronizar JSON')
                ->icon('RefreshCw')
                ->color('info')
                ->url(route('landlord.translates.execute'))
                ->actionType('header')
                ->callback(function () {
                    $translationService = app(\Callcocam\LaravelRaptor\Services\TranslationService::class);

                    try {
                        $locales = ['pt_BR', 'en', 'es', 'fr'];
                        $stats = [];

                        foreach ($locales as $locale) {
                            $stats[$locale] = $translationService->syncJsonWithDatabase($locale, null);
                        }

                        return redirect()->back()->with('success', 'Arquivos JSON sincronizados com sucesso!');
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', 'Erro ao sincronizar: '.$e->getMessage());
                    }
                }),
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

            TextField::make('name')
                ->label('Grupo')
                ->helperText('Ex: products, cart, checkout (opcional)')
                ->placeholder('Deixe vazio se não houver grupo')
                ->columnSpanFull(),

            SelectField::make('locale')
                ->label('Idioma')
                ->options(array_intersect_key($localeOptions, array_flip($availableLocales)))
                ->required()
                ->default(config('raptor.translation.default_locale', 'pt_BR'))
                ->columnSpanFull(),

            RepeaterField::make('overrides')
                ->relationship('overrides')
                ->label('Chaves e Traduções')
                ->helperText('Adicione uma ou mais chaves e seus respectivos valores traduzidos.')
                ->columnSpanFull()
                ->fields([

                    TextField::make('id')->hidden(),
                    TextField::make('key')
                        ->label('Chave')
                        ->helperText('Ex: product, add_to_cart, title')
                        ->required()
                        ->columnSpan('5'),

                    TextField::make('value')
                        ->label('Tradução')
                        ->helperText('Valor traduzido que substituirá a tradução padrão')
                        ->required()
                        ->columnSpan('7'),
                ]),
        ]);

        return $form;
    }

    /**
     * Override do store para criar grupo pai + traduções filhas
     */
    // public function store(\Illuminate\Http\Request $request)
    // {
    //     // Validar dados
    //     $validated = $request->validate([
    //         'tenant_id' => 'nullable|exists:' . config('raptor.tables.tenants', 'tenants') . ',id',
    //         'group' => 'nullable|string|max:255',
    //         'locale' => 'required|string|in:pt_BR,en,es,fr',
    //         'key_value_pairs' => 'required|array|min:1',
    //         'key_value_pairs.*.key' => 'required|string|max:255',
    //         'key_value_pairs.*.value' => 'required|string',
    //     ]);

    //     try {
    //         \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
    //             // 1. Criar grupo pai
    //             $translationGroup = \Callcocam\LaravelRaptor\Models\TranslationGroup::create([
    //                 'tenant_id' => $validated['tenant_id'] ?? null,
    //                 'group' => $validated['group'] ?? null,
    //                 'locale' => $validated['locale'],
    //             ]);

    //             // 2. Criar traduções filhas em lote
    //             $overrides = collect($validated['key_value_pairs'])->map(fn($pair) => [
    //                 'translation_group_id' => $translationGroup->id,
    //                 'key' => $pair['key'],
    //                 'value' => $pair['value'],
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ])->toArray();

    //             $translationGroup->overrides()->insert($overrides);
    //         });

    //         return redirect()
    //             ->route('translates.index')
    //             ->with('success', 'Traduções criadas com sucesso!');

    //     } catch (\Exception $e) {
    //         return back()
    //             ->withInput()
    //             ->with('error', 'Erro ao criar traduções: ' . $e->getMessage());
    //     }
    // }

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

            TextInfolist::make('name')
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
                ->value(fn ($record) => $record->getFullKey()),

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
