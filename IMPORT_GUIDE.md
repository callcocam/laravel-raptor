# Sistema Avançado de Importação Excel

Sistema robusto de importação de dados via Excel (.xlsx, .csv) com suporte a múltiplas sheets, validação, formatação e transformação de dados.

## Características Principais

- ✅ **Múltiplas Sheets**: Importe várias planilhas em um único arquivo
- ✅ **Validação Automática**: Valide dados com Laravel Validation Rules
- ✅ **Formatação de Dados**: Converta datas, números, booleanos automaticamente
- ✅ **Cast Personalizado**: Use classes de cast do Laravel ou custom
- ✅ **Mapeamento Flexível**: Use nome da coluna ou índice numérico
- ✅ **Processamento Assíncrono**: Suporte para filas (jobs)
- ✅ **Suporte a Model ou Table**: Use Eloquent Models ou acesso direto à tabela
- ✅ **Service Customizável**: Crie services personalizados para lógica complexa

## Uso Básico

### Exemplo Simples (Uma Sheet)

```php
use Callcocam\LaravelRaptor\Support\Actions\Types\ImportAction;
use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;
use Callcocam\LaravelRaptor\Support\Import\Columns\Types\ImportText;
use App\Models\Product;

ImportAction::make('products.import')
    ->useJob() // Processar em background
    ->sheets([
        Sheet::make('Produtos')
            ->modelClass(Product::class)
            ->columns([
                ImportText::make('name')
                    ->label('Nome do Produto')
                    ->required()
                    ->rules(['required', 'string', 'max:255']),

                ImportText::make('ean')
                    ->label('EAN')
                    ->unique()
                    ->rules(['required', 'string', 'max:13']),
            ])
    ])
```

### Exemplo Avançado (Múltiplas Sheets)

```php
ImportAction::make('products.import')
    ->useJob()
    ->sheets([
        // Sheet 1: Produtos (usando Model)
        Sheet::make('Produtos')
            ->modelClass(Product::class)
            ->columns([
                ImportText::make('name')
                    ->label('Nome do Produto')
                    ->required(),

                ImportNumber::make('price')
                    ->label('Preço')
                    ->float(2)
                    ->format('0.00'),

                ImportDate::make('available_at')
                    ->label('Data de Disponibilidade')
                    ->format('Y-m-d')
                    ->cast('datetime'),

                ImportBoolean::make('active')
                    ->label('Ativo')
                    ->trueValues(['sim', '1', 'ativo'])
                    ->falseValues(['não', '0', 'inativo']),

                ImportSelect::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Rascunho',
                        'published' => 'Publicado',
                        'archived' => 'Arquivado',
                    ])
                    ->defaultValue('draft'),
            ]),

        // Sheet 2: Categorias (usando tabela direta)
        Sheet::make('Categorias')
            ->table('categories', 'mysql') // Nome da tabela + database (opcional)
            ->columns([
                ImportText::make('name')
                    ->label('Nome')
                    ->unique()
                    ->required(),

                ImportNumber::make('category_id')
                    ->label('ID Categoria Pai')
                    ->integer(),
            ]),

        // Sheet 3: Com Service Customizado
        Sheet::make('Preços')
            ->modelClass(Price::class)
            ->serviceClass(CustomPriceImportService::class) // Service personalizado
            ->columns([
                // ...
            ]),
    ])
```

## Tipos de Colunas Disponíveis

### ImportText
Texto simples com conversão para string.

```php
ImportText::make('name')
    ->label('Nome')
    ->required()
    ->rules(['required', 'string', 'max:255'])
    ->defaultValue('Sem nome')
```

### ImportNumber
Números inteiros ou decimais.

```php
ImportNumber::make('price')
    ->label('Preço')
    ->float(2) // 2 casas decimais
    ->required()

ImportNumber::make('quantity')
    ->label('Quantidade')
    ->integer()
```

### ImportDate
Datas com suporte a formato Excel e string.

```php
ImportDate::make('created_at')
    ->label('Data de Criação')
    ->format('Y-m-d H:i:s')
    ->cast('datetime')
```

### ImportBoolean
Booleanos com valores customizáveis.

```php
ImportBoolean::make('active')
    ->label('Ativo')
    ->trueValues(['sim', '1', 'yes', 's'])
    ->falseValues(['não', '0', 'no', 'n'])
```

### ImportSelect
Seleção com mapeamento de opções.

```php
ImportSelect::make('status')
    ->label('Status')
    ->options([
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado',
    ])

// Múltiplas seleções
ImportSelect::make('tags')
    ->label('Tags')
    ->multiple(true, ',') // Separador: vírgula
    ->options([...])
```

## Recursos da Sheet

### Usando Model

```php
Sheet::make('Produtos')
    ->modelClass(Product::class)
    // Automaticamente detecta: tabela, conexão, etc.
```

### Usando Table Diretamente

```php
Sheet::make('Produtos')
    ->table('products') // Nome da tabela
    ->database('mysql') // Database opcional
    ->connection('tenant') // Conexão opcional
```

### Service Customizado

Crie um service personalizado estendendo `DefaultImportService`:

```php
use Callcocam\LaravelRaptor\Services\DefaultImportService;

class CustomProductImportService extends DefaultImportService
{
    protected function saveData(array $data): void
    {
        // Lógica customizada antes de salvar
        $data['slug'] = Str::slug($data['name']);
        
        parent::saveData($data);
        
        // Lógica customizada após salvar
        Cache::forget('products');
    }
}
```

E use:

```php
Sheet::make('Produtos')
    ->modelClass(Product::class)
    ->serviceClass(CustomProductImportService::class)
```

## Recursos da Column

### Mapeamento por Label ou Index

```php
// Por label (nome da coluna no Excel)
ImportText::make('name')
    ->label('Nome do Produto')

// Por índice numérico
ImportText::make('name')
    ->index(0) // Coluna A
```

### Validação

```php
ImportText::make('email')
    ->label('Email')
    ->rules(['required', 'email', 'unique:users,email'])
    ->messages([
        'required' => 'O email é obrigatório.',
        'email' => 'Email inválido.',
        'unique' => 'Email já cadastrado.',
    ])
```

### Formatação e Cast

```php
ImportDate::make('birth_date')
    ->label('Data de Nascimento')
    ->format('d/m/Y') // Formato de saída
    ->cast('datetime') // Cast para DateTime

ImportNumber::make('price')
    ->label('Preço')
    ->cast('float')
```

### Valores Padrão

```php
ImportText::make('status')
    ->defaultValue('active')

// Com closure
ImportText::make('created_at')
    ->defaultValue(fn() => now())
```

## Processamento Assíncrono

Use `useJob()` para processar em background:

```php
ImportAction::make('products.import')
    ->useJob() // Envia para fila
    ->sheets([...])
```

O usuário receberá uma notificação quando a importação for concluída.

## Estrutura do Excel

### Exemplo de Arquivo com Múltiplas Sheets

**Sheet: Produtos**
| Nome do Produto | EAN | Preço | Data de Disponibilidade | Ativo | Status |
|----------------|-------------|--------|------------------------|-------|----------|
| Produto 1 | 1234567890123 | 99.90 | 01/01/2024 | Sim | Publicado |
| Produto 2 | 9876543210987 | 149.90 | 15/01/2024 | Não | Rascunho |

**Sheet: Categorias**
| Nome | ID Categoria Pai |
|--------------|------------------|
| Eletrônicos | |
| Smartphones | 1 |
| Notebooks | 1 |

## Boas Práticas

1. **Use `unique()` para chaves únicas**: Evita duplicação
2. **Valide sempre**: Use `rules()` para garantir qualidade dos dados
3. **Use Jobs para grandes volumes**: Acima de 1000 linhas, use `useJob()`
4. **Formate datas corretamente**: Use `format()` e `cast()` juntos
5. **Crie Services customizados para lógica complexa**
6. **Use índices numéricos quando não há cabeçalho**

## Troubleshooting

### Erro: "Nome da tabela não definido"
- Certifique-se de usar `modelClass()` ou `table()`

### Validação falhando
- Verifique se as rules estão corretas
- Use `messages()` personalizadas para melhor UX

### Datas não convertendo
- Certifique-se de usar `ImportDate` e não `ImportText`
- Configure `format()` corretamente

### Performance lenta
- Use `useJob()` para processamento em background
- Ajuste `batchSize()` e `chunkSize()` no service

## Exemplos Completos

Veja o arquivo [IMPORT_EXAMPLES.md](./IMPORT_EXAMPLES.md) para mais exemplos práticos.
