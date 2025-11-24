# Sistema de Cast Automático para InfoList

## 🎯 Visão Geral

O InfoList agora possui um sistema inteligente de cast automático que formata valores baseado no tipo da coluna, usando o `CastRegistry` global.

## 🔄 Como Funciona

### 1. **Cast Automático por Tipo**

Quando você define uma coluna no InfoList, o sistema automaticamente aplica o formatador correto:

```php
use Callcocam\Raptor\Support\Info\Columns\Types\DateColumn;
use Callcocam\Raptor\Support\Info\Columns\Types\BooleanColumn;
use Callcocam\Raptor\Support\Info\Columns\Types\PhoneColumn;

InfoList::make()
    ->columns([
        DateColumn::make('created_at')->label('Criado em'),
        // ↑ Automaticamente formata como data (d/m/Y)
        
        BooleanColumn::make('active')->label('Ativo'),
        // ↑ Automaticamente converte para Sim/Não
        
        PhoneColumn::make('phone')->label('Telefone'),
        // ↑ Automaticamente formata telefone brasileiro
    ])
```

### 2. **Mapeamento Automático**

O InfoList mapeia tipos de coluna para casts do `CastRegistry`:

| Tipo da Coluna | Cast Aplicado | Exemplo |
|----------------|---------------|---------|
| `date` | `date` | `01/12/2025` |
| `datetime` | `datetime` | `01/12/2025 15:30` |
| `boolean` | `boolean` | `Sim` / `Não` |
| `status` | `status` | Badge com cor |
| `email` | `email` | Link clicável |
| `phone` | `phone` | `(11) 98765-4321` |
| `currency` | `currency` | `R$ 1.234,56` |
| `number` | `number` | `1.234,56` |

### 3. **Cast Customizado**

Você pode sobrescrever o cast automático com `castFormat()`:

```php
DateColumn::make('created_at')
    ->label('Data Especial')
    ->castFormat(function ($value, $row) {
        return Carbon::parse($value)->diffForHumans();
    })
    // Output: "2 dias atrás"
```

## 📋 Fluxo de Processamento

```
┌─────────────────────┐
│  InfoList::render() │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────────────┐
│ Para cada coluna:           │
│ 1. Pega valor do modelo     │
│ 2. Tem castCallback custom? │
└──────────┬──────────────────┘
           │
      ┌────┴────┐
      │   SIM   │   NÃO
      ↓         ↓
┌──────────┐  ┌────────────────────────┐
│ Usa cast │  │ applyCastIfAvailable() │
│ customiz.│  │ - Mapeia tipo → cast   │
└──────────┘  │ - Busca no Registry    │
              │ - Aplica formatador    │
              └──────────┬─────────────┘
                         │
                         ↓
              ┌────────────────────┐
              │ column->render()   │
              │ (renderização final)│
              └────────────────────┘
```

## 🛠️ Implementação Detalhada

### InfoList::applyCastIfAvailable()

```php
protected function applyCastIfAvailable($column, $value, $data)
{
    // 1. Se tem cast customizado, usa ele
    if ($column->castCallback && is_callable($column->castCallback)) {
        return call_user_func($column->castCallback, $value, $data);
    }
    
    // 2. Detecta tipo da coluna
    $type = $column->getType();
    
    // 3. Mapeia para cast do registry
    $castMap = [
        'date' => 'date',
        'datetime' => 'datetime',
        'boolean' => 'boolean',
        'status' => 'status',
        'email' => 'email',
        'phone' => 'phone',
        'currency' => 'currency',
        'number' => 'number',
    ];
    
    // 4. Aplica cast se disponível
    if (isset($castMap[$type])) {
        $formatter = CastRegistry::get($castMap[$type]);
        
        if ($formatter && is_callable($formatter)) {
            return $formatter($value, $data);
        }
    }
    
    return $value;
}
```

## 📦 Tipos de Coluna Disponíveis

### 1. **TextColumn** (Padrão)
```php
TextColumn::make('name')
    ->prefix('Sr.')
    ->suffix('Jr.')
```

### 2. **DateColumn**
```php
DateColumn::make('created_at')
    ->format('d/m/Y H:i')  // Customiza formato
    ->icon('Calendar')
```

### 3. **BooleanColumn**
```php
BooleanColumn::make('active')
    ->labels('Ativo', 'Inativo')
    ->icons('CheckCircle', 'XCircle')
```

### 4. **StatusColumn**
```php
StatusColumn::make('status')
    ->badge()  // Renderiza como badge colorido
```

### 5. **EmailColumn**
```php
EmailColumn::make('email')
    // Automaticamente cria link clicável
```

### 6. **PhoneColumn**
```php
PhoneColumn::make('phone')
    // Formata telefone brasileiro automaticamente
```

### 7. **CardColumn** (com sub-colunas)
```php
CardColumn::make('address')
    ->columns([
        TextColumn::make('street'),
        TextColumn::make('city'),
        TextColumn::make('state'),
    ])
```

## 🔧 Criando Nova Coluna com Cast Automático

```php
namespace App\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class CurrencyColumn extends Column
{
    protected string $type = 'currency';  // ← Tipo mapeia para cast
    
    protected ?string $component = 'info-column-currency';
    
    public function render(mixed $value, $row = null): mixed
    {
        if ($value === null) {
            return $this->getDefault() ?? 'R$ 0,00';
        }
        
        // Cast já foi aplicado pelo InfoList antes de chegar aqui!
        // $value já está formatado como "R$ 1.234,56"
        
        return $value;
    }
}
```

## 🎨 Uso em Resource

```php
class UserResource extends AbstractResource
{
    public static function infolist(): InfoList
    {
        return InfoList::make()
            ->columns([
                TextColumn::make('name')->label('Nome'),
                EmailColumn::make('email')->label('E-mail'),
                PhoneColumn::make('phone')->label('Telefone'),
                DateColumn::make('created_at')->label('Cadastrado em'),
                BooleanColumn::make('active')->label('Ativo'),
                
                // Com cast customizado
                DateColumn::make('updated_at')
                    ->label('Última atualização')
                    ->castFormat(fn($v) => Carbon::parse($v)->diffForHumans()),
            ]);
    }
}
```

## 🌟 Benefícios

### 1. **DRY (Don't Repeat Yourself)**
- Não precisa formatar manualmente em cada coluna
- Cast centralizado no CastRegistry

### 2. **Consistência**
- Todos os campos de data formatam igual
- Todos os telefones formatam igual
- Padrão unificado em toda aplicação

### 3. **Flexibilidade**
- Cast automático quando conveniente
- Override fácil quando necessário
- Suporte a casts customizados

### 4. **Manutenibilidade**
- Muda formato de data em um lugar
- Afeta toda a aplicação
- Fácil debug

## 🔍 Debug

Para ver qual cast está sendo aplicado:

```php
// Em InteractWithForm.php, adicione:
protected function applyCastIfAvailable($column, $value, $data)
{
    $type = $column->getType();
    
    logger()->debug("InfoList Cast", [
        'column' => $column->getName(),
        'type' => $type,
        'has_custom_cast' => isset($column->castCallback),
        'original_value' => $value,
    ]);
    
    // ... resto do código
}
```

## 📝 Checklist para Nova Coluna

Ao criar uma nova coluna com cast automático:

- ✅ Definir `protected string $type = 'seu_tipo'`
- ✅ Adicionar mapeamento em `applyCastIfAvailable()` se necessário
- ✅ Registrar cast no `CastRegistry` se não existir
- ✅ Documentar comportamento padrão
- ✅ Suportar cast customizado via `castFormat()`

## 🚀 Próximos Passos

### Implementações Futuras:

1. **Cache de Formatadores**
   - Cache de resultado do CastRegistry::get()
   - Performance em listas grandes

2. **Cast Condicional**
   ```php
   DateColumn::make('expires_at')
       ->castWhen(fn($value) => $value > now(), 'date_future')
   ```

3. **Cast Composto**
   ```php
   TextColumn::make('full_address')
       ->castUsing(['address', 'city', 'state'])
   ```

4. **Validação de Cast**
   - Verificar se cast existe antes de aplicar
   - Fallback gracioso se falhar
