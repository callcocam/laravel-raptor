<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use Callcocam\LaravelRaptor\Http\Controllers\LandlordController;
use Callcocam\LaravelRaptor\Models\SocialProvider;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SelectField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField;
use Callcocam\LaravelRaptor\Support\Form\Form;
use Callcocam\LaravelRaptor\Support\Info\Columns\Types\TextColumn as TextInfolist;
use Callcocam\LaravelRaptor\Support\Info\InfoList as InfoListBuilder;
use Callcocam\LaravelRaptor\Support\Pages\Index;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\BooleanColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\DateColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;

class SocialProviderController extends LandlordController
{
    public function model(): ?string
    {
        return config('raptor.landlord.models.social_provider', SocialProvider::class);
    }

    public function getPages(): array
    {
        return [
            'index' => Index::route('/social-providers')
                ->label(__('Login Social'))
                ->name('social-providers.index')
                ->icon('Share2')
                ->group('Segurança')
                ->groupCollapsible(true)
                ->order(25)
                ->resource(SocialProvider::class)
                ->middlewares(['auth', 'verified']),
        ];
    }

    protected function form(Form $form): Form
    {
        $providers = SocialProvider::availableProviders();

        $form->columns([
            SelectField::make('tenant_id')
                ->label('Tenant')
                ->required()
                ->relationship('tenant', 'name')
                ->rules(['required', 'exists:tenants,id'])
                ->columnSpan('6'),

            SelectField::make('provider')
                ->label('Provider')
                ->required()
                ->options(array_combine($providers, array_map('ucfirst', $providers)))
                ->rules(['required', 'in:'.implode(',', $providers)])
                ->placeholder('Selecione o provider')
                ->columnSpan('6'),

            TextField::make('name')
                ->label('Nome / Label')
                ->required()
                ->rules(['required', 'string', 'max:255'])
                ->placeholder('Ex: Entrar com Google')
                ->columnSpan('12'),

            TextField::make('client_id')
                ->label('Client ID')
                ->required()
                ->rules(['required', 'string', 'max:1000'])
                ->placeholder('OAuth Client ID fornecido pelo provider')
                ->columnSpan('6'),

            TextField::make('client_secret')
                ->label('Client Secret')
                ->required()
                ->rules(['required', 'string', 'max:1000'])
                ->placeholder('OAuth Client Secret — salvo criptografado')
                ->columnSpan('6'),

            TextField::make('redirect_uri')
                ->label('Redirect URI')
                ->rules(['nullable', 'url', 'max:500'])
                ->placeholder('https://seudominio.com/auth/social/google/callback')
                ->columnSpan('12'),

            SelectField::make('status')
                ->label('Status')
                ->required()
                ->options(['draft' => 'Inativo', 'published' => 'Ativo'])
                ->default('draft')
                ->columnSpan('12'),
        ]);

        return $form;
    }

    protected function table(TableBuilder $table): TableBuilder
    {
        $table->columns([
            TextColumn::make('tenant.name')
                ->label('Tenant')
                ->sortable()
                ->searchable(),

            TextColumn::make('provider')
                ->label('Provider')
                ->sortable(),

            TextColumn::make('name')
                ->label('Nome')
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
            \Callcocam\LaravelRaptor\Support\Table\Filters\SelectFilter::make('provider')
                ->label('Provider')
                ->options(array_combine(
                    SocialProvider::availableProviders(),
                    array_map('ucfirst', SocialProvider::availableProviders())
                )),
            \Callcocam\LaravelRaptor\Support\Table\Filters\TrashedFilter::make(),
        ]);

        $table->actions([
            \Callcocam\LaravelRaptor\Support\Actions\Types\ViewAction::make('social-providers.show'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\EditAction::make('social-providers.edit'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\RestoreAction::make('social-providers.restore'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\ForceDeleteAction::make('social-providers.forceDelete'),
            \Callcocam\LaravelRaptor\Support\Actions\Types\DeleteAction::make('social-providers.destroy'),
        ]);

        $table->headerActions([
            \Callcocam\LaravelRaptor\Support\Actions\Types\CreateAction::make('social-providers.create'),
        ]);

        return $table;
    }

    protected function infoList(InfoListBuilder $infoList): InfoListBuilder
    {
        $infoList->columns([
            TextInfolist::make('tenant.name')->label('Tenant'),
            TextInfolist::make('provider')->label('Provider'),
            TextInfolist::make('name')->label('Nome'),
            TextInfolist::make('client_id')->label('Client ID'),
            TextInfolist::make('redirect_uri')->label('Redirect URI'),
            TextInfolist::make('status')->label('Status'),
        ]);

        return $infoList;
    }

    protected function resourcePath(): string
    {
        return 'landlord';
    }
}
