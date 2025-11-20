# Exemplos de Uso das Colunas Personalizadas

## TextColumn
Coluna de texto simples.

```php
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn;

TextColumn::make('name', 'Nome')
    ->searchable()
    ->sortable();
```

## EmailColumn
Coluna para exibir emails com formatação especial.

```php
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\EmailColumn;

EmailColumn::make('email', 'E-mail')
    ->searchable()
    ->sortable();
```

## DateColumn
Coluna para formatar datas.

```php
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\DateColumn;

// Formato personalizado
DateColumn::make('created_at', 'Criado em')
    ->format('d/m/Y H:i:s')
    ->sortable();

// Data relativa (ex: "há 2 dias")
DateColumn::make('updated_at', 'Atualizado')
    ->relative()
    ->sortable();
```

## BooleanColumn
Coluna para valores booleanos com ícones e cores personalizadas.

```php
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\BooleanColumn;

BooleanColumn::make('active', 'Ativo')
    ->trueLabel('Ativo')
    ->falseLabel('Inativo')
    ->trueColor('success')
    ->falseColor('destructive')
    ->trueIcon('Check')
    ->falseIcon('X')
    ->sortable();
```

## PhoneColumn
Coluna para exibir telefones com máscara brasileira.

```php
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\PhoneColumn;

// Com máscara automática
PhoneColumn::make('phone', 'Telefone')
    ->mask()
    ->searchable();

// Sem máscara
PhoneColumn::make('phone', 'Telefone')
    ->mask(false);
```

## StatusColumn
Coluna para exibir status com cores e ícones personalizados.

```php
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\StatusColumn;

// Método 1: Definir status individualmente
StatusColumn::make('status', 'Status')
    ->status('pending', 'Pendente', 'warning', 'Clock')
    ->status('approved', 'Aprovado', 'success', 'Check')
    ->status('rejected', 'Rejeitado', 'destructive', 'X')
    ->sortable();

// Método 2: Definir múltiplos status de uma vez
StatusColumn::make('status', 'Status')
    ->statuses([
        'pending' => [
            'label' => 'Pendente',
            'color' => 'warning',
            'icon' => 'Clock',
        ],
        'approved' => [
            'label' => 'Aprovado',
            'color' => 'success',
            'icon' => 'Check',
        ],
        'rejected' => [
            'label' => 'Rejeitado',
            'color' => 'destructive',
            'icon' => 'X',
        ],
    ])
    ->sortable();

// Método 3: Apenas labels
StatusColumn::make('status', 'Status')
    ->statuses([
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado',
    ])
    ->defaultColor('secondary')
    ->sortable();
```

## MoneyColumn
Coluna para formatar valores monetários.

```php
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\MoneyColumn;

// Reais (padrão)
MoneyColumn::make('price', 'Preço')
    ->sortable();

// Dólar
MoneyColumn::make('price_usd', 'Preço USD')
    ->currency('USD')
    ->locale('en_US')
    ->sortable();

// Euro
MoneyColumn::make('price_eur', 'Preço EUR')
    ->currency('EUR')
    ->locale('pt_PT')
    ->sortable();
```

## ImageColumn
Coluna para exibir imagens.

```php
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\ImageColumn;

// Imagem quadrada
ImageColumn::make('avatar', 'Avatar')
    ->size(40)
    ->rounded()
    ->defaultImage('/images/default-avatar.png');

// Imagem retangular
ImageColumn::make('banner', 'Banner')
    ->size(80, 40)
    ->defaultImage('/images/default-banner.png');
```

## BadgeColumn
Coluna para exibir badges com cores personalizadas.

```php
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\BadgeColumn;

// Método 1: Mapa de cores
BadgeColumn::make('priority', 'Prioridade')
    ->colors([
        'high' => 'destructive',
        'medium' => 'warning',
        'low' => 'success',
    ])
    ->defaultColor('secondary')
    ->sortable();

// Método 2: Definir cores individualmente
BadgeColumn::make('priority', 'Prioridade')
    ->color('high', 'destructive')
    ->color('medium', 'warning')
    ->color('low', 'success')
    ->sortable();
```

## Exemplo Completo em um Controller

```php
<?php

namespace App\Http\Controllers\Tenant;

use Callcocam\LaravelRaptor\Http\Controllers\AbstractController;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\TextColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\EmailColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\DateColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\BooleanColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\PhoneColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\StatusColumn;
use Callcocam\LaravelRaptor\Support\Table\Columns\Types\MoneyColumn;
use Callcocam\LaravelRaptor\Support\Table\TableBuilder;

class UserController extends AbstractController
{
    protected function table(TableBuilder $table): TableBuilder
    {
        return $table
            ->columns([
                TextColumn::make('name', 'Nome')
                    ->searchable()
                    ->sortable(),

                EmailColumn::make('email', 'E-mail')
                    ->searchable()
                    ->sortable(),

                PhoneColumn::make('phone', 'Telefone')
                    ->mask()
                    ->searchable(),

                StatusColumn::make('status', 'Status')
                    ->statuses([
                        'active' => ['label' => 'Ativo', 'color' => 'success', 'icon' => 'Check'],
                        'inactive' => ['label' => 'Inativo', 'color' => 'destructive', 'icon' => 'X'],
                        'pending' => ['label' => 'Pendente', 'color' => 'warning', 'icon' => 'Clock'],
                    ])
                    ->sortable(),

                BooleanColumn::make('verified', 'Verificado')
                    ->trueLabel('Sim')
                    ->falseLabel('Não')
                    ->sortable(),

                MoneyColumn::make('balance', 'Saldo')
                    ->sortable(),

                DateColumn::make('created_at', 'Criado em')
                    ->format('d/m/Y H:i')
                    ->sortable(),

                DateColumn::make('updated_at', 'Atualizado')
                    ->relative()
                    ->sortable(),
            ]);
    }
}
```
