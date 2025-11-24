# FormFieldRepeater - Guia de Uso

## Visão Geral

O `RepeaterField` permite criar campos repetíveis em formulários, onde o usuário pode adicionar/remover múltiplas instâncias de um grupo de campos.

## Exemplo Básico

```php
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\RepeaterField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField;

RepeaterField::make('enderecos', 'Endereços')
    ->fields([
        TextField::make('rua')->label('Rua')->required(),
        TextField::make('numero')->label('Número'),
        TextField::make('cidade')->label('Cidade'),
    ])
```

## Métodos Disponíveis

### `minItems(int $min)`
Define o número mínimo de itens. Usuário não poderá remover abaixo deste limite.

```php
->minItems(1) // Pelo menos 1 item obrigatório
```

### `maxItems(int $max)`
Define o número máximo de itens. Botão "Adicionar" fica desabilitado ao atingir o limite.

```php
->maxItems(5) // Máximo 5 itens
```

### `addButtonLabel(string $label)`
Customiza o texto do botão adicionar.

```php
->addButtonLabel('Adicionar endereço')
```

### `removeButtonLabel(string $label)`
Customiza o texto do botão remover.

```php
->removeButtonLabel('Remover endereço')
```

### `defaultItems(array $items)`
Define itens pré-preenchidos ao carregar o formulário vazio.

```php
->defaultItems([
    ['rua' => '', 'numero' => '', 'cidade' => ''],
])
```

### `collapsible(bool $collapsible = true)`
Permite que os itens sejam colapsáveis (expandir/recolher).

```php
->collapsible() // Habilita colapso
```

### `orderable(bool $orderable = true)`
Permite reordenar itens com drag & drop (feature futura).

```php
->orderable() // Habilita reordenação
```

### `fields(array $fields)`
Define os campos de cada item do repeater.

```php
->fields([
    TextField::make('nome')->columnSpan('6'),
    TextField::make('email')->columnSpan('6'),
])
```

## Exemplo Completo

```php
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\RepeaterField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SelectField;

RepeaterField::make('contatos', 'Contatos de Emergência')
    ->minItems(1)
    ->maxItems(3)
    ->addButtonLabel('Adicionar contato')
    ->removeButtonLabel('Remover')
    ->collapsible()
    ->fields([
        TextField::make('nome')
            ->label('Nome Completo')
            ->required()
            ->columnSpan('6'),
        
        TextField::make('telefone')
            ->label('Telefone')
            ->required()
            ->columnSpan('6'),
        
        TextField::make('email')
            ->label('E-mail')
            ->columnSpan('8'),
        
        SelectField::make('parentesco')
            ->label('Parentesco')
            ->options([
                'pai' => 'Pai',
                'mae' => 'Mãe',
                'irmao' => 'Irmão/Irmã',
                'conjuge' => 'Cônjuge',
                'amigo' => 'Amigo',
            ])
            ->columnSpan('4'),
    ])
```

## Grid Layout nos Campos

Os campos dentro do repeater suportam `columnSpan` para controlar o layout:

```php
->fields([
    TextField::make('campo1')->columnSpan('12'),     // Ocupa linha inteira
    TextField::make('campo2')->columnSpan('6'),      // Metade da linha
    TextField::make('campo3')->columnSpan('6'),      // Metade da linha
    TextField::make('campo4')->columnSpan('4'),      // 1/3 da linha
    TextField::make('campo5')->columnSpan('4'),      // 1/3 da linha
    TextField::make('campo6')->columnSpan('4'),      // 1/3 da linha
])
```

## Validação

Para validar campos de um repeater, use a notação de array no Laravel:

```php
$request->validate([
    'enderecos' => 'required|array|min:1',
    'enderecos.*.rua' => 'required|string',
    'enderecos.*.numero' => 'nullable|string',
    'enderecos.*.cidade' => 'required|string',
]);
```

## Salvando no Banco

O repeater retorna um array de objetos. Você pode salvar como JSON ou em uma tabela relacionada:

### Opção 1: JSON no banco
```php
$table->json('enderecos')->nullable();

// No model
protected $casts = [
    'enderecos' => 'array',
];
```

### Opção 2: Tabela relacionada
```php
// Migration
Schema::create('enderecos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('rua');
    $table->string('numero')->nullable();
    $table->string('cidade');
});

// No controller ao salvar
$user->enderecos()->delete(); // Remove antigos
foreach ($request->enderecos as $endereco) {
    $user->enderecos()->create($endereco);
}
```

## Campos Suportados

Todos os tipos de campos podem ser usados dentro do repeater:

- `TextField`
- `TextareaField`
- `SelectField`
- `NumberField`
- `DateField`
- `CheckboxField`
- `EmailField`
- `PasswordField`
- `FileUploadField`
- `ComboboxField`

## Limitações Atuais

- Drag & drop para reordenação ainda não implementado (orderable é placeholder)
- Campos aninhados (repeater dentro de repeater) não testados
